<?php

namespace App\Modules\Administration\Filament\Resources\BookingOperationsResource\Pages;

use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingOperationsResource::class;
}
