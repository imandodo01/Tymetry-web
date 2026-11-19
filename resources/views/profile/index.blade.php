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

    @if ($errors->has('current_password'))
    <div class="alert alert-danger">{{ $errors->first('current_password') }}</div>
    @endif
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
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 px-4">
                    <h6 class="mb-0 fw-semibold text-muted">Authenticator-based two-factor authentication</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">
                        Protect sign-in with a time-based code from an authenticator app. Email verification remains a separate account-verification step.
                    </p>

                    <p class="mb-3">
                        Email status:
                        @if (auth()->user()->email_verified_at)
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-warning text-dark">Not verified</span>
                        @endif
                    </p>

                    @if (auth()->user()->two_factor_enabled && auth()->user()->two_factor_method === 'totp')
                        <div class="alert alert-success">
                            Authenticator app is enabled. Use its six-digit code when signing in.
                        </div>

                        <form method="POST" action="{{ route('profile.two-factor.recovery-codes') }}" class="mb-4">
                            @csrf
                            <div class="mb-3">
                                <label for="recovery_current_password" class="form-label">Confirm current password to regenerate recovery codes</label>
                                <input type="password" id="recovery_current_password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-outline-dark btn-sm">Regenerate recovery codes</button>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('profile.two-factor') }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="two_factor_enabled" value="0">
                            <div class="mb-3">
                                <label for="disable_current_password" class="form-label">Confirm current password to disable MFA</label>
                                <input type="password" id="disable_current_password" name="current_password" class="form-control" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm">Disable authenticator MFA</button>
                        </form>
                    @else
                        @if (auth()->user()->two_factor_enabled && auth()->user()->two_factor_method === 'email')
                            <div class="alert alert-warning">
                                This account still uses the previous email-based second factor. It remains active until authenticator setup is confirmed.
                            </div>
                        @else
                            <div class="alert alert-light border">
                                Authenticator MFA is currently disabled.
                            </div>
                        @endif

                        @if ($pendingTotpUri)
                            <h6 class="fw-semibold">1. Add Tymetry to your authenticator app</h6>
                            <p class="small text-muted">
                                Choose manual setup in your authenticator app and enter this secret. Keep it private.
                            </p>
                            <div class="mb-3">
                                <label class="form-label">Account</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->email }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Setup key</label>
                                <input type="text" class="form-control font-monospace" value="{{ auth()->user()->two_factor_secret }}" readonly>
                            </div>
                            <details class="mb-4">
                                <summary class="small">Show advanced provisioning URI</summary>
                                <textarea class="form-control font-monospace mt-2" rows="3" readonly>{{ $pendingTotpUri }}</textarea>
                            </details>

                            <h6 class="fw-semibold">2. Confirm authenticator setup</h6>
                            <form method="POST" action="{{ route('profile.two-factor.confirm') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="totp_confirmation_code" class="form-label">Six-digit code from your authenticator app</label>
                                    <input type="text" id="totp_confirmation_code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control @error('code') is-invalid @enderror" required autocomplete="one-time-code">
                                    @error('code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm">Confirm and enable authenticator MFA</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('profile.two-factor.setup') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="setup_current_password" class="form-label">Confirm current password to start setup</label>
                                    <input type="password" id="setup_current_password" name="current_password" class="form-control" required autocomplete="current-password">
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm">Set up authenticator app</button>
                            </form>
                        @endif

                        @if (auth()->user()->two_factor_enabled)
                            <hr>
                            <form method="POST" action="{{ route('profile.two-factor') }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="two_factor_enabled" value="0">
                                <div class="mb-3">
                                    <label for="legacy_disable_current_password" class="form-label">Or disable the existing second factor</label>
                                    <input type="password" id="legacy_disable_current_password" name="current_password" class="form-control" required autocomplete="current-password">
                                </div>
                                <button type="submit" class="btn btn-outline-danger btn-sm">Disable email-based MFA</button>
                            </form>
                        @endif
                    @endif

                    @if ($errors->has('two_factor_enabled'))
                        <div class="alert alert-danger mt-3">{{ $errors->first('two_factor_enabled') }}</div>
                    @endif

                    @if (session('recovery_codes'))
                        <div class="alert alert-warning mt-4">
                            <strong>Save these recovery codes now.</strong>
                            They are shown only once. Each code can be used once if your authenticator app is unavailable.
                            <ul class="font-monospace mt-2 mb-0">
                                @foreach (session('recovery_codes') as $recoveryCode)
                                    <li>{{ $recoveryCode }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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