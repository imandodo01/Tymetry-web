<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;
use Throwable;

class OtpController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->has('pending_auth_user_id') || !$request->session()->has('pending_auth_purpose')) {
            return redirect()->route('auth.index');
        }

        return view('auth.otp', [
            'purpose' => $request->session()->get('pending_auth_purpose'),
        ]);
    }

    public function send(Request $request, OtpService $otp)
    {
        $user = $this->pendingUser($request);
        $purpose = $request->session()->get('pending_auth_purpose');

        if (!$user || !in_array($purpose, ['email_verification', 'two_factor'], true)) {
            return redirect()->route('auth.index');
        }

        try {
            $otp->send($user, $purpose);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['code' => 'Unable to send a code right now. Please try again later.']);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    public function verify(Request $request, OtpService $otp, TotpService $totp)
    {
        $purpose = $request->session()->get('pending_auth_purpose');
        $codeRules = $purpose === 'totp'
            ? ['required', 'string', 'regex:/\\A(?:\\d{6}|[A-Z0-9]{10})\\z/i']
            : ['required', 'digits:6'];

        $validated = $request->validate(['code' => $codeRules]);

        $user = $this->pendingUser($request);

        if (!$user || !in_array($purpose, ['email_verification', 'two_factor', 'totp'], true)) {
            $request->session()->forget(['pending_auth_user_id', 'pending_auth_purpose', 'pending_auth_remember']);
            return redirect()->route('auth.index');
        }

        $totpAttemptKey = $purpose === 'totp'
            ? 'totp-login:' . $user->getKey()
            : null;

        if ($totpAttemptKey !== null) {
            // Serialize the account-level limit check, TOTP verification, and
            // counter update so concurrent requests cannot pass the same pre-check.
            $verificationResult = Cache::lock('totp-login-verification:' . $user->getKey(), 30)
                ->get(function () use ($totpAttemptKey, $totp, $user, $validated): string {
                    if (RateLimiter::tooManyAttempts($totpAttemptKey, 5)) {
                        return 'limited';
                    }

                    if (!$totp->verifyLogin($user, $validated['code'])) {
                        RateLimiter::hit($totpAttemptKey, 60);

                        return 'invalid';
                    }

                    RateLimiter::clear($totpAttemptKey);

                    return 'verified';
                });

            if ($verificationResult === false) {
                return back()->withErrors([
                    'code' => 'Another authenticator verification is in progress. Please wait a moment and try again.',
                ]);
            }

            if ($verificationResult === 'limited') {
                return back()->withErrors([
                    'code' => 'Too many invalid authenticator attempts. Please wait before trying again.',
                ]);
            }

            if ($verificationResult !== 'verified') {
                return back()->withErrors(['code' => 'The code is invalid or expired. Please try again or request a new code.']);
            }
        } elseif (!$otp->verify($user, $purpose, $validated['code'])) {
            return back()->withErrors(['code' => 'The code is invalid or expired. Please try again or request a new code.']);
        }

        if ($purpose === 'email_verification') {
            $user->forceFill(['email_verified_at' => now()])->save();

            if ($user->two_factor_enabled && $user->two_factor_method === 'totp') {
                $request->session()->put('pending_auth_purpose', 'totp');

                return redirect()->route('otp.index')
                    ->with('status', 'Email verified. Enter the code from your authenticator app.');
            }

            if ($user->two_factor_enabled) {
                $request->session()->put('pending_auth_purpose', 'two_factor');

                try {
                    $otp->send($user, 'two_factor');
                } catch (Throwable $exception) {
                    report($exception);
                    return redirect()->route('otp.index')->withErrors([
                        'code' => 'Your email is verified, but we could not send the second-factor code. Please request a new code.',
                    ]);
                }

                return redirect()->route('otp.index')->with('status', 'Email verified. A second-factor code has been sent.');
            }
        }

        $remember = (bool) $request->session()->get('pending_auth_remember', false);

        Auth::login($user, $remember);
        $request->session()->forget(['pending_auth_user_id', 'pending_auth_purpose', 'pending_auth_remember']);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_auth_user_id');

        return $userId ? User::find($userId) : null;
    }
}
