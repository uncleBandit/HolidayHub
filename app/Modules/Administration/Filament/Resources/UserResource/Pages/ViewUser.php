<?php

namespace App\Modules\Administration\Filament\Resources\UserResource\Pages;

use App\Modules\Administration\Filament\Resources\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * A single platform account.
 */
class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
