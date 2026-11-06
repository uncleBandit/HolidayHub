<?php

namespace App\Modules\Administration\Filament\Resources\DestinationResource\Pages;

use App\Modules\Administration\Filament\Concerns\AuditsRecordChanges;
use App\Modules\Administration\Filament\Resources\DestinationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDestination extends CreateRecord
{
    use AuditsRecordChanges;

    protected static string $resource = DestinationResource::class;

    protected static function auditPrefix(): string
    {
        return 'destinations';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = DestinationResource::applyHeroUpload($data);
        $data['meta_data'] = ['source' => 'admin', 'created_by_user_id' => auth()->id()];

        return $data;
    }
}
