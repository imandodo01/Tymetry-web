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
    public function send(User $user, string $purpose): void
    {
        if (!in_array($purpose, ['email_verification', 'two_factor'], true)) {
            throw new RuntimeException('Unsupported OTP purpose.');
        }

        $key = $this->key($user, $purpose);
        $existing = Cache::get($key);

        if ($existing && now()->timestamp - $existing['sent_at'] < config('authentication.otp_resend_seconds', 60)) {
            throw new RuntimeException('Please wait before requesting another code.');
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($key, [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('authentication.otp_expiry_minutes', 5))->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
        ], now()->addMinutes(config('authentication.otp_expiry_minutes', 5)));

        Mail::raw(
            "Your Tymetry verification code is {$code}. It expires in "
                . config('authentication.otp_expiry_minutes', 5)
                . " minutes. If you did not request this code, you can ignore this email.",
            function ($message) use ($user, $purpose): void {
                $message->to($user->email)
                    ->subject($purpose === 'two_factor' ? 'Your Tymetry sign-in code' : 'Verify your Tymetry email');
            }
        );
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $key = $this->key($user, $purpose);
        $challenge = Cache::get($key);

        if (!$challenge || now()->timestamp > $challenge['expires_at']) {
            Cache::forget($key);
            return false;
        }

        if ($challenge['attempts'] >= config('authentication.otp_max_attempts', 5)) {
            Cache::forget($key);
            return false;
        }

        if (!Hash::check($code, $challenge['hash'])) {
            $challenge['attempts']++;
            Cache::put($key, $challenge, max(1, $challenge['expires_at'] - now()->timestamp));
            return false;
        }

        Cache::forget($key);

        return true;
    }

    public function clear(User $user, string $purpose): void
    {
        Cache::forget($this->key($user, $purpose));
    }

    private function key(User $user, string $purpose): string
    {
        return 'auth:otp:' . $purpose . ':' . $user->getKey() . ':' . Str::lower($user->email);
    }
}
