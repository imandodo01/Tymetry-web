@extends('layout.app-plain')

@section('title', 'Login')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
@endpush

@section('content')
<div class="login-wrapper">

    <div class="login-card">

        <div class="login-header">
            <h2>WELCOME</h2>
            <p>Sign in to continue</p>
        </div>
        <form method="POST" action="{{ route('auth.login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Enter your email" name="email" value="{{ old('email') }}">
                @error('email')
                <span class="invalid-feedback d-block">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">
                    Password
                </label>

                <div class="position-relative">
                    <input type="password" class="form-control pe-5 @error('password') is-invalid @enderror"
                        placeholder="Enter your password" name="password" id="password">

                    <button type="button" id="togglePassword" class="password-toggle-btn">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                @error('password')
                <span class="invalid-feedback d-block">
                    {{ $message }}
                </span>
                @enderror

            </div>

            <div class="mb-4 d-flex justify-content-end align-items-center gap-2">
                <input type="checkbox" name="remember" id="remember">

                <label for="remember" class="mb-0">
                    Remember Me
                </label>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-login">
                    Login
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('password.index') }}" class="forgot-link">
                    Forgot Password?
                </a>
            </div>

        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const icon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function () {

        const isPassword = password.getAttribute('type') === 'password';

        password.setAttribute(
            'type',
            isPassword ? 'text' : 'password'
        );

        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
</script>
@endpush