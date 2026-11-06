<?php

namespace App\Modules\Administration\Filament\Resources\AmenityResource\Pages;

use App\Modules\Administration\Filament\Concerns\AuditsRecordChanges;
use App\Modules\Administration\Filament\Resources\AmenityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAmenity extends CreateRecord
{
    use AuditsRecordChanges;

    protected static string $resource = AmenityResource::class;

    protected static function auditPrefix(): string
    {
        return 'amenities';
    }
}
