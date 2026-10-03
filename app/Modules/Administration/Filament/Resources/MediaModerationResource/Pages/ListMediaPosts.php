<?php

namespace App\Modules\Administration\Filament\Resources\MediaModerationResource\Pages;

use App\Modules\Administration\Filament\Resources\MediaModerationResource;
use Filament\Resources\Pages\ListRecords;

class ListMediaPosts extends ListRecords
{
    protected static string $resource = MediaModerationResource::class;
}
