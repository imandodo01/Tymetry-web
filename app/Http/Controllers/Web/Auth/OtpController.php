<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function verify(Request $request, OtpService $otp)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->pendingUser($request);
        $purpose = $request->session()->get('pending_auth_purpose');

        if (!$user || !in_array($purpose, ['email_verification', 'two_factor'], true)) {
            $request->session()->forget(['pending_auth_user_id', 'pending_auth_purpose', 'pending_auth_remember']);
            return redirect()->route('auth.index');
        }

        if (!$otp->verify($user, $purpose, $validated['code'])) {
            return back()->withErrors(['code' => 'The code is invalid or expired. Please try again or request a new code.']);
        }

        if ($purpose === 'email_verification') {
            $user->forceFill(['email_verified_at' => now()])->save();

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

        Auth::login($user, $request->session()->boolean('pending_auth_remember'));
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
