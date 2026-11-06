<?php

namespace App\Modules\Administration\Filament\Resources\DestinationResource\Pages;

use App\Modules\Administration\Filament\Concerns\AuditsRecordChanges;
use App\Modules\Administration\Filament\Resources\DestinationResource;
use Filament\Resources\Pages\EditRecord;

class EditDestination extends EditRecord
{
    use AuditsRecordChanges;

    protected static string $resource = DestinationResource::class;

    protected static function auditPrefix(): string
    {
        return 'destinations';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return DestinationResource::applyHeroUpload($data);
    }
}
