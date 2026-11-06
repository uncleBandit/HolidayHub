<?php

namespace App\Modules\Administration\Filament\Resources\DestinationResource\Pages;

use App\Modules\Administration\Filament\Resources\DestinationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDestinations extends ListRecords
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
