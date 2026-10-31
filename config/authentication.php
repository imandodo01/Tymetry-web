<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Login Verification Test Bypass
    |--------------------------------------------------------------------------
    |
    | This bypass is deliberately limited to local/testing environments.
    | It only skips email-verification OTPs; it never skips optional 2FA.
    |
    */
    'login_verification_bypass' => env('OTP_LOGIN_BYPASS', false)
        && in_array(env('APP_ENV', 'production'), ['local', 'testing'], true),

    'otp_expiry_minutes' => 5,
    'otp_max_attempts' => 5,
    'otp_resend_seconds' => 60,
];
