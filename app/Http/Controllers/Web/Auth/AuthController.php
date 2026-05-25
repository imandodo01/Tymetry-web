<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        // VALIDATION
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // LOGIN ATTEMPT
        if (Auth::attempt($credentials, $request->remember)) {

            // REGENERATE SESSION
            $request->session()->regenerate();

            // REDIRECT AFTER LOGIN
            return redirect()->intended('/dashboard');
        }

        // LOGIN FAILED
        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        // LOGOUT USER
        Auth::logout();

        // INVALIDATE SESSION
        $request->session()->invalidate();

        // REGENERATE CSRF TOKEN
        $request->session()->regenerateToken();

        // REDIRECT TO LOGIN
        return redirect('/login');
    }
}
