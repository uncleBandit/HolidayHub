<?php

namespace App\Modules\Media\Domain\Enums;

enum MediaAssetStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
