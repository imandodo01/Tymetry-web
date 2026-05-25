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
                            <input type="password" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror">

                            @error('current_password')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                            <span class="invalid-feedback">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-outline-dark btn-sm px-4">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
