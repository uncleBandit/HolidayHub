<?php

namespace App\Modules\Accommodation\Domain\Enums;

enum AccommodationStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
