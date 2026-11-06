<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
use App\Modules\Booking\Domain\Models\Booking;
use App\Shared\Support\Currencies;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The most recent bookings across the marketplace, each opening the full
 * booking record. Package component bookings are folded into their package.
 */
class LatestBookings extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('bookings.view') === true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest bookings')
            ->query(
                Booking::query()
                    ->whereNull('package_booking_id')
                    ->with(['guest', 'bookable'])
                    ->latest()
            )
            ->defaultPaginationPageOption(8)
            ->paginated([8])
            ->columns([
                TextColumn::make('confirmation_code')->label('Code')->fontFamily('mono')->placeholder('—'),
                TextColumn::make('guest')
                    ->label('Guest')
                    ->state(fn (Booking $b) => trim(($b->guest?->first_name ?? '').' '.($b->guest?->last_name ?? '')) ?: ($b->guest?->email ?? '—')),
                TextColumn::make('bookable')
                    ->label('Listing')
                    ->state(fn (Booking $b) => $b->bookable?->name ?? '—')
                    ->description(fn (Booking $b) => str($b->bookable_type)->replace('_', ' ')->headline()->toString())
                    ->limit(40),
                TextColumn::make('check_in_date')->label('Dates')->date('j M')
                    ->description(fn (Booking $b) => $b->check_out_date ? '→ '.$b->check_out_date->format('j M Y') : null),
                TextColumn::make('total_amount')->label('Total')
                    ->state(fn (Booking $b) => Currencies::format($b->total_amount, $b->currency))
                    ->alignEnd(),
                TextColumn::make('status')->badge()->color(fn (?string $state) => match ($state) {
                    'confirmed', 'checked_in' => 'success',
                    'checked_out', 'completed' => 'gray',
                    'pending' => 'warning',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('payment_status')->label('Payment')->badge()->color(fn (?string $state) => match ($state) {
                    'paid' => 'success',
                    'pending' => 'warning',
                    'refunded' => 'gray',
                    'failed' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('created_at')->label('Booked')->since(),
            ])
            ->recordUrl(fn (Booking $b) => BookingOperationsResource::getUrl('view', ['record' => $b]));
    }
}
