<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\Auth\TotpService;
use RuntimeException;

class ProfileController extends Controller
{
    public function index(TotpService $totp)
    {
        $user = auth()->user();
        $pendingTotpUri = $user->two_factor_secret && !$user->two_factor_confirmed_at
            ? $totp->provisioningUri($user, $user->two_factor_secret)
            : null;

        return view('profile.index', compact('pendingTotpUri'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => bcrypt($validated['password']),
        ]);

        return back()->with('success', 'Password updated.');
    }

    public function twoFactor(Request $request, TotpService $totp)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        if ($request->boolean('two_factor_enabled')) {
            return back()->withErrors([
                'two_factor_enabled' => 'Enable two-factor authentication by setting up an authenticator app.',
            ]);
        }

        $totp->disable($request->user());

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function twoFactorSetup(Request $request, TotpService $totp)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        if (!$request->user()->email_verified_at) {
            return back()->withErrors([
                'two_factor_enabled' => 'Verify your email address before enabling two-factor authentication.',
            ]);
        }

        try {
            $totp->beginSetup($request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['two_factor_enabled' => $exception->getMessage()]);
        }

        return back()->with('success', 'Authenticator setup started. Add the account to your authenticator app, then confirm the six-digit code.');
    }

    public function twoFactorConfirm(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $recoveryCodes = $totp->confirmSetup($request->user(), $validated['code']);

        if ($recoveryCodes === null) {
            return back()->withErrors(['code' => 'The authenticator code is invalid or the setup session has expired.']);
        }

        return back()
            ->with('success', 'Authenticator-based two-factor authentication enabled.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function twoFactorRecoveryCodes(Request $request, TotpService $totp)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        try {
            $recoveryCodes = $totp->regenerateRecoveryCodes($request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['two_factor_enabled' => $exception->getMessage()]);
        }

        return back()
            ->with('success', 'New recovery codes generated. Previous recovery codes are no longer valid.')
            ->with('recovery_codes', $recoveryCodes);
    }
}
