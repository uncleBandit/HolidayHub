<?php

namespace App\Modules\Administration\Filament\Resources\UserResource\Pages;

use App\Modules\Administration\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit a platform account's profile and role assignments.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
