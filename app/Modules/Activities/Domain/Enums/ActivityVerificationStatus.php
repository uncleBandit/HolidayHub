<?php

namespace App\Modules\Activities\Domain\Enums;

enum ActivityVerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
