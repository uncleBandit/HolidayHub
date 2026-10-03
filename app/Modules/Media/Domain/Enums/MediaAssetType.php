<?php

namespace App\Modules\Media\Domain\Enums;

enum MediaAssetType: string
{
    case Original = 'original';
    case Thumbnail = 'thumbnail';
}
