<?php

namespace App\Modules\Administration\Filament\Resources\AgentResource\Pages;

use App\Modules\Administration\Filament\Resources\AgentResource;
use Filament\Resources\Pages\ListRecords;

class ListAgents extends ListRecords
{
    protected static string $resource = AgentResource::class;
}
