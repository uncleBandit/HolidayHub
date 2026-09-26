<?php

namespace App\Modules\Administration\Filament\Resources\UserResource\Pages;

use App\Modules\Administration\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

/**
 * All platform accounts, newest first.
 */
class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
