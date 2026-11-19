<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class OtpService
{
    private const LOCK_SECONDS = 30;

    public function send(User $user, string $purpose): void
    {
        $this->assertSupportedPurpose($purpose);

        $processed = Cache::lock($this->lockKey($user, $purpose), self::LOCK_SECONDS)
            ->get(function () use ($user, $purpose): bool {
                $key = $this->key($user, $purpose);
                $existing = Cache::get($key);

                if ($existing && now()->timestamp - $existing['sent_at'] < config('authentication.otp_resend_seconds', 60)) {
                    throw new RuntimeException('Please wait before requesting another code.');
                }

                $code = (string) random_int(100000, 999999);
                $expiresAt = now()->addMinutes(config('authentication.otp_expiry_minutes', 5));

                Mail::raw(
                    "Your Tymetry verification code is {$code}. It expires in "
                        . config('authentication.otp_expiry_minutes', 5)
                        . " minutes. If you did not request this code, you can ignore this email.",
                    function ($message) use ($user, $purpose): void {
                        $message->to($user->email)
                            ->subject($purpose === 'two_factor' ? 'Your Tymetry sign-in code' : 'Verify your Tymetry email');
                    }
                );

                Cache::put($key, [
                    'hash' => Hash::make($code),
                    'expires_at' => $expiresAt->timestamp,
                    'sent_at' => now()->timestamp,
                    'attempts' => 0,
                ], $expiresAt);

                return true;
            });

        if ($processed !== true) {
            throw new RuntimeException('A verification request is already being processed. Please try again.');
        }
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $this->assertSupportedPurpose($purpose);

        $result = Cache::lock($this->lockKey($user, $purpose), self::LOCK_SECONDS)
            ->get(function () use ($user, $purpose, $code): array {
                $key = $this->key($user, $purpose);
                $challenge = Cache::get($key);

                if (!$challenge || now()->timestamp >= $challenge['expires_at']) {
                    Cache::forget($key);

                    return ['verified' => false];
                }

                $maxAttempts = (int) config('authentication.otp_max_attempts', 5);

                if ($challenge['attempts'] >= $maxAttempts) {
                    Cache::forget($key);

                    return ['verified' => false];
                }

                if (!Hash::check($code, $challenge['hash'])) {
                    $challenge['attempts']++;

                    if ($challenge['attempts'] >= $maxAttempts) {
                        Cache::forget($key);
                    } else {
                        Cache::put(
                            $key,
                            $challenge,
                            max(1, $challenge['expires_at'] - now()->timestamp)
                        );
                    }

                    return ['verified' => false];
                }

                Cache::forget($key);

                return ['verified' => true];
            });

        return is_array($result) && $result['verified'] === true;
    }

    public function clear(User $user, string $purpose): void
    {
        $this->assertSupportedPurpose($purpose);

        $cleared = Cache::lock($this->lockKey($user, $purpose), self::LOCK_SECONDS)
            ->get(function () use ($user, $purpose): bool {
                Cache::forget($this->key($user, $purpose));

                return true;
            });

        if ($cleared !== true) {
            throw new RuntimeException('A verification request is already being processed. Please try again.');
        }
    }

    private function assertSupportedPurpose(string $purpose): void
    {
        if (!in_array($purpose, ['email_verification', 'two_factor'], true)) {
            throw new RuntimeException('Unsupported OTP purpose.');
        }
    }

    private function key(User $user, string $purpose): string
    {
        return 'auth:otp:' . $purpose . ':' . $user->getKey() . ':' . Str::lower($user->email);
    }

    private function lockKey(User $user, string $purpose): string
    {
        return $this->key($user, $purpose) . ':lock';
    }
}
