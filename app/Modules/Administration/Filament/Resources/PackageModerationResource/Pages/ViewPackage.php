<?php

namespace App\Modules\Administration\Filament\Resources\PackageModerationResource\Pages;

use App\Modules\Administration\Filament\Resources\PackageModerationResource;
use App\Modules\Packages\Application\Services\PackagePricing;
use App\Modules\Packages\Domain\Models\Package;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPackage extends ViewRecord
{
    protected static string $resource = PackageModerationResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return $this->loadForDisplay(parent::resolveRecord($key));
    }

    /**
     * Livewire rehydrates the record without its relations on every request
     * (e.g. when an action runs), so load them again rather than lazy load.
     */
    public function hydrate(): void
    {
        if ($this->record instanceof Package) {
            $this->loadForDisplay($this->record);
        }
    }

    private function loadForDisplay(Model $record): Model
    {
        $record->loadMissing(['agent', 'destination', 'scenes', 'images']);
        app(PackagePricing::class)->ensureLoaded($record);

        return $record;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicPage')
                ->label('View public page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (Package $record): ?string => PackageModerationResource::publicUrl($record), shouldOpenInNewTab: true)
                ->visible(fn (Package $record): bool => PackageModerationResource::publicUrl($record) !== null),
            ...PackageModerationResource::moderationActions(),
        ];
    }
}
