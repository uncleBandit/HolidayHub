<?php

namespace App\Modules\Administration\Filament\Resources\ActivityCategoryResource\Pages;

use App\Modules\Administration\Filament\Concerns\AuditsRecordChanges;
use App\Modules\Administration\Filament\Resources\ActivityCategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditActivityCategory extends EditRecord
{
    use AuditsRecordChanges;

    protected static string $resource = ActivityCategoryResource::class;

    protected static function auditPrefix(): string
    {
        return 'activity_categories';
    }
}
