@extends('layout.app-plain')

@section('title', 'Login')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
@endpush

@section('content')
<div class="login-wrapper">

    <div class="login-card">

        <div class="login-header">
            <h2>FORGOT PASSWORD</h2>
            <p>
                Enter your email to receive password reset link
            </p>
        </div>

        @if (session('status'))
        <div class="alert alert-dark mb-4">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.resetLink') }}">
            @csrf

            <div class="mb-4">
                <label class="form-label">
                    Email Address
                </label>

                <input type="email" name="email" value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror" placeholder="Enter your email">

                @error('email')
                <span class="invalid-feedback d-block">
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-login">
                    Send Reset Link
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('auth.index') }}" class="forgot-link">
                    Back to Login
                </a>
            </div>

        </form>

    </div>

</div>
@endsection