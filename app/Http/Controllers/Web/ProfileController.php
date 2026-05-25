<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email'],
        ]);

        $request->user()->update($validated);

        return back()->with(
            'success',
            'Profile updated.'
        );
    }

    public function password(Request $request)
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password'
            ],

            'password' => [
                'required',
                'confirmed',
                'min:8'
            ],
        ]);

        $request->user()->update([
            'password' => bcrypt(
                $validated['password']
            )
        ]);

    return back()->with(
            'success',
            'Password updated.'
        );
    }
}
