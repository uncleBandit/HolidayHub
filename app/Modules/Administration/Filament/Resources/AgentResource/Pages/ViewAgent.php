<?php

namespace App\Modules\Administration\Filament\Resources\AgentResource\Pages;

use App\Modules\Administration\Filament\Resources\AgentResource;
use App\Modules\Agents\Domain\Models\Agent;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewAgent extends ViewRecord
{
    protected static string $resource = AgentResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return $this->loadForDisplay(parent::resolveRecord($key));
    }

    /**
     * Livewire rehydrates the record without relations on each request.
     */
    public function hydrate(): void
    {
        if ($this->record instanceof Agent) {
            $this->loadForDisplay($this->record);
        }
    }

    private function loadForDisplay(Model $record): Model
    {
        $record->loadMissing(['user']);
        $record->load(['packages' => fn ($query) => $query->latest()]);

        return $record;
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return AgentResource::moderationActions();
    }
}
