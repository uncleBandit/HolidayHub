<?php

namespace App\Modules\Administration\Filament\Resources\ActivityModerationResource\Pages;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Filament\Resources\ActivityModerationResource;
use App\Modules\Administration\Filament\Resources\ProviderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityModerationResource::class;

    /**
     * Media, sessions and options are only needed on the moderation view.
     */
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
        if ($this->record instanceof Activity) {
            $this->loadForDisplay($this->record);
        }
    }

    private function loadForDisplay(Model $record): Model
    {
        $record->loadMissing([
            'provider', 'destination', 'category', 'schedules', 'verificationHistory.reviewer',
            'scenes', 'images', 'sessions', 'options',
        ]);

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
                ->url(fn (Activity $record): ?string => ActivityModerationResource::publicUrl($record), shouldOpenInNewTab: true)
                ->visible(fn (Activity $record): bool => ActivityModerationResource::publicUrl($record) !== null),
            Action::make('provider')
                ->label('Provider')
                ->icon('heroicon-o-building-storefront')
                ->color('gray')
                ->url(fn (Activity $record): ?string => $record->provider
                    ? ProviderResource::getUrl('view', ['record' => $record->provider])
                    : null)
                ->visible(fn (Activity $record): bool => $record->provider !== null && ProviderResource::canViewAny()),
            ...ActivityModerationResource::moderationActions(),
        ];
    }
}
