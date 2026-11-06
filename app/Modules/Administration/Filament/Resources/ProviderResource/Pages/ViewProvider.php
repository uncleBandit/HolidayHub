<?php

namespace App\Modules\Administration\Filament\Resources\ProviderResource\Pages;

use App\Modules\Administration\Filament\Resources\ProviderResource;
use App\Modules\Providers\Domain\Models\Provider;
use Filament\Resources\Pages\ViewRecord;

class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    /**
     * Livewire rehydrates the record without relations on each request.
     */
    public function hydrate(): void
    {
        if ($this->record instanceof Provider) {
            $this->record->loadMissing('user');
        }
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return ProviderResource::moderationActions();
    }
}
