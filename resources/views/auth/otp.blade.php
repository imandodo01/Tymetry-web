@extends('layout.app-plain')

@section('title', 'OTP Verification')

@push('styles')
<link rel="stylesheet" href="assets/css/otp.css">
@endpush

@section('content')
<div class="otp-wrapper">

    <div class="otp-card">

        <div class="otp-header text-center">
            <h2>OTP VERIFICATION</h2>
            <p>Enter the verification code sent to your email or WhatsApp</p>
        </div>

        <form>

            <div class="otp-input-group mb-4">
                <input type="text" maxlength="1" class="otp-input">
                <input type="text" maxlength="1" class="otp-input">
                <input type="text" maxlength="1" class="otp-input">
                <input type="text" maxlength="1" class="otp-input">
                <input type="text" maxlength="1" class="otp-input">
                <input type="text" maxlength="1" class="otp-input">
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-verify">
                    Verify OTP
                </button>
            </div>

            <div class="text-center otp-footer">
                <p>
                    Didn't receive code?
                    <a href="#">Resend OTP</a>
                </p>
            </div>

        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
    const inputs = document.querySelectorAll('.otp-input');

    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });
</script>
@endpush