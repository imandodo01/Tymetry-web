<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function index()
    {
        return view('auth.otp');
    }

    public function send(Request $request) {}

    public function verify(Request $request) {}
}
