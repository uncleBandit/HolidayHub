<?php

namespace App\Modules\Administration\Filament\Resources\ActivityCategoryResource\Pages;

use App\Modules\Administration\Filament\Resources\ActivityCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityCategories extends ListRecords
{
    protected static string $resource = ActivityCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
