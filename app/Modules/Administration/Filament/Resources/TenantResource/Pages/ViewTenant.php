<?php

namespace App\Modules\Administration\Filament\Resources\TenantResource\Pages;

use App\Modules\Administration\Filament\Resources\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * A single tenant application, with its full verification history.
 */
class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        // Only the admin's own notes and the decision fields are editable here.
        // Status changes are deliberately not an Edit form: they must go through
        // the workflow actions, which enforce transitions and record history.
        return [
            EditAction::make(),
        ];
    }
}
