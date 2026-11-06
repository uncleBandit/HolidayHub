<?php

namespace App\Modules\Administration\Filament\Resources\AmenityResource\Pages;

use App\Modules\Administration\Filament\Resources\AmenityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAmenities extends ListRecords
{
    protected static string $resource = AmenityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
