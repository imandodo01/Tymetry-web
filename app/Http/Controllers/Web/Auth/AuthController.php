<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request, OtpService $otp)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::validate($credentials)) {
            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->firstOrFail();

        if (!$user->email_verified_at && config('authentication.login_verification_bypass')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if (!$user->email_verified_at) {
            return $this->beginVerification($request, $user, 'email_verification', $otp);
        }

        if ($user->two_factor_enabled) {
            return $this->beginVerification($request, $user, 'two_factor', $otp);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    private function beginVerification(Request $request, User $user, string $purpose, OtpService $otp)
    {
        try {
            $otp->send($user, $purpose);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()])->onlyInput('email');
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['email' => 'Unable to send a verification code right now. Please try again later.'])
                ->onlyInput('email');
        }

        $request->session()->put([
            'pending_auth_user_id' => $user->id,
            'pending_auth_purpose' => $purpose,
            'pending_auth_remember' => $request->boolean('remember'),
        ]);

        return redirect()->route('otp.index')->with('status', 'A verification code has been sent to your email address.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
