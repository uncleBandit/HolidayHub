<?php

namespace App\Modules\Media\Domain\Enums;

enum MediaPostStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
