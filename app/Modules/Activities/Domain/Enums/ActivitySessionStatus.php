<?php

namespace App\Modules\Activities\Domain\Enums;

enum ActivitySessionStatus: string
{
    case Scheduled = 'scheduled';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
