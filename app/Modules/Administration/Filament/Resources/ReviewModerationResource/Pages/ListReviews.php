<?php

namespace App\Modules\Administration\Filament\Resources\ReviewModerationResource\Pages;

use App\Modules\Administration\Filament\Resources\ReviewModerationResource;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewModerationResource::class;
}
