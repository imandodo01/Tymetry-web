@extends('layout.app')

@section('title', 'Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-semibold mb-1">
            Profile Settings
        </h4>
        <small class="text-muted">
            Manage your account information and security settings
        </small>
    </div>
    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold text-muted">Profile Information</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', auth()->user()->name) }}">
                            @error('name')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', auth()->user()->email) }}">
                            @error('email')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm px-4">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Change Password -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold text-muted">Security</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="form-label">Current Password</label>
                            <div class="position-relative">
                                <input type="password" name="current_password" id="current_password"
                                    class="form-control password pe-5 @error('current_password') is-invalid @enderror">

                                <button type="button" id="toggleCurrentPassword" class="password-toggle-btn">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                            @error('current_password')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">New Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" id="password"
                                    class="form-control password pe-5 @error('password') is-invalid @enderror">

                                <button type="button" id="togglePassword" class="password-toggle-btn">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="form-control password pe-5 @error('password') is-invalid @enderror">

                                <button type="button" id="togglePasswordConfirmation" class="password-toggle-btn">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-outline-dark btn-sm px-4">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold text-muted">Two-Factor Authentication</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">
                        When enabled, signing in requires your password and a one-time code sent to your verified email.
                    </p>
                    <p>
                        Email status:
                        @if (auth()->user()->email_verified_at)
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-warning text-dark">Not verified</span>
                        @endif
                    </p>

                    <form method="POST" action="{{ route('profile.two-factor') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="two_factor_enabled" class="form-label">Two-factor authentication</label>
                            <select id="two_factor_enabled" name="two_factor_enabled"
                                class="form-select @error('two_factor_enabled') is-invalid @enderror">
                                <option value="0" @selected(!auth()->user()->two_factor_enabled)>Disabled</option>
                                <option value="1" @selected(auth()->user()->two_factor_enabled)>Enabled</option>
                            </select>
                            @error('two_factor_enabled')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="two_factor_current_password" class="form-label">Confirm current password</label>
                            <input type="password" id="two_factor_current_password" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-dark btn-sm px-4">Save security setting</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const toggleCurrentPassword = document.getElementById('toggleCurrentPassword');
    const currentPassword = document.getElementById('current_password');
    const iconCurrent = toggleCurrentPassword.querySelector('i');

    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const icon = togglePassword.querySelector('i');

    const togglePasswordConfirm = document.getElementById('togglePasswordConfirmation');
    const passwordConfirm = document.getElementById('password_confirmation');
    const iconConfirm = togglePasswordConfirm.querySelector('i');

    toggleCurrentPassword.addEventListener('click', function () {

        const isPassword = currentPassword.getAttribute('type') === 'password';

        currentPassword.setAttribute(
            'type',
            isPassword ? 'text' : 'password'
        );

        iconCurrent.classList.toggle('bi-eye');
        iconCurrent.classList.toggle('bi-eye-slash');
    });

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