<?php

namespace App\Enums\Auth;

enum OtpVerificationResult: string
{
    case Verified = 'verified';
    case Invalid = 'invalid';
    case Busy = 'busy';
}
