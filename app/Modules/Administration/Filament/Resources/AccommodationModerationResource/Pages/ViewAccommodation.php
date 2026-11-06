<?php

namespace App\Modules\Administration\Filament\Resources\AccommodationModerationResource\Pages;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Administration\Filament\Resources\AccommodationModerationResource;
use App\Modules\Administration\Filament\Resources\ProviderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewAccommodation extends ViewRecord
{
    protected static string $resource = AccommodationModerationResource::class;

    /**
     * The moderation view previews media and room types, which the list does
     * not need, so they are loaded here only.
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
        if ($this->record instanceof Accommodation) {
            $this->loadForDisplay($this->record);
        }
    }

    private function loadForDisplay(Model $record): Model
    {
        /** @var Accommodation $record */
        $record->loadMissing(['provider', 'destination', 'verificationHistory.reviewer']);
        $record->loadMorph('bookable', [
            Hotel::class => ['scenes', 'images', 'roomTypes'],
            Villa::class => ['scenes'],
            BedAndBreakfast::class => ['scenes', 'images'],
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
                ->url(fn (Accommodation $record): ?string => AccommodationModerationResource::publicUrl($record), shouldOpenInNewTab: true)
                ->visible(fn (Accommodation $record): bool => AccommodationModerationResource::publicUrl($record) !== null),
            Action::make('provider')
                ->label('Provider')
                ->icon('heroicon-o-building-storefront')
                ->color('gray')
                ->url(fn (Accommodation $record): ?string => $record->provider
                    ? ProviderResource::getUrl('view', ['record' => $record->provider])
                    : null)
                ->visible(fn (Accommodation $record): bool => $record->provider !== null && ProviderResource::canViewAny()),
            ...AccommodationModerationResource::moderationActions(),
        ];
    }
}
