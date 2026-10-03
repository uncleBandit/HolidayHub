<?php

namespace App\Modules\Activities\Domain\Enums;

enum ActivityStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
