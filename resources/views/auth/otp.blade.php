@extends('layout.app-plain')

@section('title', $purpose === 'totp' ? 'Authenticator Verification' : ($purpose === 'two_factor' ? 'Two-Factor Authentication' : 'Email Verification'))

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/otp.css') }}">
@endpush

@section('content')
<div class="otp-wrapper">
    <div class="otp-card">
        <div class="otp-header text-center">
            <h2>{{ $purpose === 'totp' ? 'AUTHENTICATOR VERIFICATION' : ($purpose === 'two_factor' ? 'TWO-FACTOR AUTHENTICATION' : 'EMAIL VERIFICATION') }}</h2>
            <p>
                {{ $purpose === 'totp'
                    ? 'Enter the six-digit code from your authenticator app. You can use a recovery code if your device is unavailable.'
                    : ($purpose === 'two_factor'
                        ? 'Enter the sign-in code sent to your email to finish logging in.'
                        : 'Verify your email address by entering the code sent to your inbox.') }}
            </p>
        </div>

        @if (session('status'))
        <div class="alert alert-dark">{{ session('status') }}</div>
        @endif

        @error('code')
        <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('otp.verify') }}">
            @csrf
            <div class="mb-4">
                <label for="code" class="form-label">
                    {{ $purpose === 'totp' ? 'Authenticator or recovery code' : 'Six-digit verification code' }}
                </label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                    pattern="{{ $purpose === 'totp' ? '([0-9]{6}|[A-Za-z0-9]{10})' : '[0-9]{6}' }}"
                    maxlength="{{ $purpose === 'totp' ? 10 : 6 }}" required autofocus
                    class="form-control @error('code') is-invalid @enderror" aria-describedby="code-help">
                <small id="code-help" class="text-muted">
                    {{ $purpose === 'totp' ? 'Authenticator codes change every 30 seconds. Recovery codes can be used once.' : 'The code expires in five minutes.' }}
                </small>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-verify">Verify</button>
            </div>
        </form>

        @if ($purpose !== 'totp')
            <form method="POST" action="{{ route('otp.send') }}" class="text-center">
                @csrf
                <button type="submit" class="btn btn-link">Resend code</button>
            </form>
        @endif
    </div>
</div>
@endsection
