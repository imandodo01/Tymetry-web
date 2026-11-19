<?php

namespace App\Services\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class TotpService
{
    private const SECRET_BYTES = 20;
    private const PERIOD_SECONDS = 30;
    private const CODE_DIGITS = 6;
    private const RECOVERY_CODE_COUNT = 8;

    public function beginSetup(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->two_factor_enabled && $lockedUser->two_factor_method === 'totp') {
                throw new RuntimeException('Disable the current authenticator before replacing it.');
            }

            $secret = $this->base32Encode(random_bytes(self::SECRET_BYTES));

            $lockedUser->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_last_used_counter' => null,
            ])->save();

            $this->recordSecurityEvent($lockedUser, 'mfa_setup_started', 'Started authenticator-based MFA enrollment.');

            return $secret;
        });
    }

    public function provisioningUri(User $user, string $secret): string
    {
        $issuer = (string) config('app.name', 'Tymetry');
        $label = rawurlencode($issuer . ':' . $user->email);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_DIGITS,
            'period' => self::PERIOD_SECONDS,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/' . $label . '?' . $query;
    }

    public function matchingCounter(string $secret, string $code, ?int $timestamp = null): ?int
    {
        if (!preg_match('/\A\d{6}\z/', $code) || !preg_match('/\A[A-Z2-7]{32}\z/i', $secret)) {
            return null;
        }

        $timestamp ??= now()->timestamp;
        $currentCounter = intdiv($timestamp, self::PERIOD_SECONDS);
        $key = $this->base32Decode($secret);

        for ($offset = -1; $offset <= 1; $offset++) {
            $counter = $currentCounter + $offset;

            if ($counter >= 0 && hash_equals($this->codeForCounter($key, $counter), $code)) {
                return $counter;
            }
        }

        return null;
    }

    public function confirmSetup(User $user, string $code): ?array
    {
        return DB::transaction(function () use ($user, $code): ?array {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (!$lockedUser->two_factor_secret || $lockedUser->two_factor_confirmed_at) {
                return null;
            }

            $counter = $this->matchingCounter($lockedUser->two_factor_secret, $code);

            if ($counter === null) {
                return null;
            }

            $recoveryCodes = $this->newRecoveryCodes();

            $lockedUser->forceFill([
                'two_factor_enabled' => true,
                'two_factor_method' => 'totp',
                'two_factor_confirmed_at' => now(),
                'two_factor_last_used_counter' => $counter,
                'two_factor_recovery_codes' => array_map(
                    static fn (string $recoveryCode): string => Hash::make($recoveryCode),
                    $recoveryCodes
                ),
            ])->save();

            $this->recordSecurityEvent($lockedUser, 'mfa_enabled', 'Enabled authenticator-based MFA.');

            return $recoveryCodes;
        });
    }

    public function verifyLogin(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());

            if (
                !$lockedUser
                || !$lockedUser->two_factor_enabled
                || $lockedUser->two_factor_method !== 'totp'
                || !$lockedUser->two_factor_secret
                || !$lockedUser->two_factor_confirmed_at
            ) {
                return false;
            }

            $counter = $this->matchingCounter((string) $lockedUser->two_factor_secret, $code);
            $lastUsedCounter = $lockedUser->two_factor_last_used_counter;

            if ($counter !== null && ($lastUsedCounter === null || $counter > $lastUsedCounter)) {
                $lockedUser->forceFill(['two_factor_last_used_counter' => $counter])->save();
                $this->recordSecurityEvent($lockedUser, 'mfa_totp_verified', 'Completed sign-in using an authenticator code.');

                return true;
            }

            $recoveryCodes = $lockedUser->two_factor_recovery_codes ?? [];

            foreach ($recoveryCodes as $index => $recoveryCodeHash) {
                if (Hash::check(Str::upper($code), $recoveryCodeHash)) {
                    unset($recoveryCodes[$index]);
                    $lockedUser->forceFill([
                        'two_factor_recovery_codes' => array_values($recoveryCodes),
                    ])->save();
                    $this->recordSecurityEvent($lockedUser, 'mfa_recovery_code_used', 'Completed sign-in using a recovery code.');

                    return true;
                }
            }

            return false;
        });
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (
                !$lockedUser->two_factor_enabled
                || $lockedUser->two_factor_method !== 'totp'
                || !$lockedUser->two_factor_secret
                || !$lockedUser->two_factor_confirmed_at
            ) {
                throw new RuntimeException('Authenticator-based two-factor authentication is not enabled.');
            }

            $recoveryCodes = $this->newRecoveryCodes();

            $lockedUser->forceFill([
                'two_factor_recovery_codes' => array_map(
                    static fn (string $recoveryCode): string => Hash::make($recoveryCode),
                    $recoveryCodes
                ),
            ])->save();

            $this->recordSecurityEvent($lockedUser, 'mfa_recovery_codes_regenerated', 'Regenerated authenticator recovery codes.');

            return $recoveryCodes;
        });
    }

    public function disable(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $lockedUser->forceFill([
                'two_factor_enabled' => false,
                'two_factor_method' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_last_used_counter' => null,
            ])->save();

            $this->recordSecurityEvent($lockedUser, 'mfa_disabled', 'Disabled multi-factor authentication.');
        });
    }

    private function recordSecurityEvent(User $user, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id' => $user->getKey(),
            'todo_id' => null,
            'action' => $action,
            'description' => $description,
        ]);
    }

    private function newRecoveryCodes(): array
    {
        return array_map(
            static fn (): string => Str::upper(Str::random(10)),
            range(1, self::RECOVERY_CODE_COUNT)
        );
    }

    private function codeForCounter(string $key, int $counter): string
    {
        $message = pack('N2', ($counter >> 32) & 0xFFFFFFFF, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $message, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $binaryCode = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binaryCode % (10 ** self::CODE_DIGITS)), self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bitsLeft = 0;
        $encoded = '';

        foreach (unpack('C*', $data) as $byte) {
            $buffer = ($buffer << 8) | $byte;
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $encoded .= $alphabet[($buffer >> $bitsLeft) & 0x1F];
            }

            $buffer &= (1 << $bitsLeft) - 1;
        }

        if ($bitsLeft > 0) {
            $encoded .= $alphabet[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $encoded = strtoupper(rtrim($encoded, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $decoded = '';

        foreach (str_split($encoded) as $character) {
            $value = strpos($alphabet, $character);

            if ($value === false) {
                throw new RuntimeException('Invalid authenticator secret.');
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }

            $buffer &= (1 << $bitsLeft) - 1;
        }

        return $decoded;
    }
}
