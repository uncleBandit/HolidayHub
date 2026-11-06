<?php

namespace App\Modules\Administration\Filament\Resources\BookingOperationsResource\Pages;

use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingOperationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookingOperationsResource::markPaidAction(),
            BookingOperationsResource::refundAction(),
            BookingOperationsResource::overrideAction(),
            BookingOperationsResource::cancelAction(),
        ];
    }
}
