@extends('layout.app-plain')

@section('title', $purpose === 'two_factor' ? 'Two-Factor Authentication' : 'Email Verification')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/otp.css') }}">
@endpush

@section('content')
<div class="otp-wrapper">
    <div class="otp-card">
        <div class="otp-header text-center">
            <h2>{{ $purpose === 'two_factor' ? 'TWO-FACTOR AUTHENTICATION' : 'EMAIL VERIFICATION' }}</h2>
            <p>
                {{ $purpose === 'two_factor'
                    ? 'Enter the sign-in code sent to your email to finish logging in.'
                    : 'Verify your email address by entering the code sent to your inbox.' }}
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
                <label for="code" class="form-label">Six-digit verification code</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                    pattern="[0-9]{6}" maxlength="6" required autofocus
                    class="form-control @error('code') is-invalid @enderror" aria-describedby="code-help">
                <small id="code-help" class="text-muted">The code expires in five minutes.</small>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-verify">Verify</button>
            </div>
        </form>

        <form method="POST" action="{{ route('otp.send') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link">Resend code</button>
        </form>
    </div>
</div>
@endsection
