<?php

namespace App\Modules\Administration\Filament\Resources\OfferResource\Pages;

use App\Modules\Administration\Filament\Resources\OfferResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOffer extends ViewRecord
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [OfferResource::toggleAction()];
    }
}
