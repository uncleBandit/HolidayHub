<?php

namespace App\Modules\Audit\Filament\Resources\AuditLogResource\Pages;

use App\Modules\Audit\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;
}
