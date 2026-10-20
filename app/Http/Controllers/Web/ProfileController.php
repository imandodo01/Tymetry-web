<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        if ($validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

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

    public function twoFactor(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'two_factor_enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        if ($request->boolean('two_factor_enabled') && !$user->email_verified_at) {
            return back()->withErrors([
                'two_factor_enabled' => 'Verify your email address before enabling two-factor authentication.',
            ]);
        }

        $user->update([
            'two_factor_enabled' => $request->boolean('two_factor_enabled'),
        ]);

        return back()->with('success', $user->two_factor_enabled
            ? 'Two-factor authentication enabled.'
            : 'Two-factor authentication disabled.');
    }
}
