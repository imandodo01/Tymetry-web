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
            <h2>RESET PASSWORD</h2>
            <p>
                Create your new password
            </p>
        </div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-3" style="display:none;">
                <label class="form-label">
                    Email Address
                </label>

                <input type="email" name="email" value="{{ $email ?? old('email') }}"
                    class="form-control @error('email') is-invalid @enderror" placeholder="Enter your email">

                @error('email')
                <span class="invalid-feedback d-block">
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">
                    New Password
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

            <div class="mb-4">
                <label class="form-label">
                    Confirm Password
                </label>

                {{-- <input type="password" name="password_confirmation" class="form-control"
                    placeholder="Confirm password"> --}}
                <div class="position-relative">
                    <input type="password" class="form-control pe-5 @error('password') is-invalid @enderror"
                        placeholder="Enter your password" name="password_confirmation" id="passwordConfirmation">

                    <button type="button" id="togglePasswordConfirm" class="password-toggle-btn">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-login">
                    Reset Password
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
@push('scripts')
<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const icon = togglePassword.querySelector('i');

    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordConfirm = document.getElementById('passwordConfirmation');
    const iconConfirm = togglePasswordConfirm.querySelector('i');

    togglePassword.addEventListener('click', function () {

        const isPassword = password.getAttribute('type') === 'password';

        password.setAttribute(
            'type',
            isPassword ? 'text' : 'password'
        );

        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });

    togglePasswordConfirm.addEventListener('click', function () {

        const isPassword = passwordConfirm.getAttribute('type') === 'password';

        passwordConfirm.setAttribute(
            'type',
            isPassword ? 'text' : 'password'
        );

        iconConfirm.classList.toggle('bi-eye');
        iconConfirm.classList.toggle('bi-eye-slash');
    });
</script>
@endpush