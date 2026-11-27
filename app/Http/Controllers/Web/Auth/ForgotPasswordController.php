<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    // FORGOT PASSWORD PAGE
    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    // SEND RESET LINK
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors([
                'email' => __($status),
            ]);
    }

    // RESET PASSWORD PAGE
    public function resetPassword(string $token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->input('email')
        ]);
    }

    // UPDATE PASSWORD
    public function updatePassword(Request $request)
    {
        $request->validate([]);
        $validator = Validator::make(
            $request->all(),
            [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'confirmed', 'min:8'],
            ],
            [
                'email.required' => 'Link is invalid.',
                'email.email' => 'Link is invalid.',
            ]
        );

        if ($validator->fails()) {

            $errors = $validator->errors();

            throw ValidationException::withMessages([
                'password' => $errors->first('email') ?: $errors->first('password'),
            ]);
        }

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user, $password) {

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('status', __($status))
            : back()->withErrors([
                'password' => [__($status)],
            ]);
    }
}
