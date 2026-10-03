<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\BookingOperationsResource\Pages;
use App\Modules\Booking\Domain\Models\Booking;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingOperationsResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Booking operations';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('bookings.view') === true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('bookings.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('confirmation_code')->label('Booking reference')->searchable()->sortable()->weight('bold'),
                TextColumn::make('guest.user.name')->label('Guest')->searchable()->placeholder('—'),
                TextColumn::make('bookable.name')->label('Listing')->searchable()->placeholder('—'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('payment_status')->badge()->sortable(),
                TextColumn::make('check_in_date')->date()->sortable(),
                TextColumn::make('check_out_date')->date()->sortable(),
                TextColumn::make('total_amount')->money(fn (Booking $record): string => $record->currency)->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'checked_in' => 'Checked in',
                    'checked_out' => 'Checked out',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('payment_status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([\Filament\Actions\ViewAction::make()])
            ->emptyStateHeading('No bookings found');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Booking')
                ->columns(2)
                ->schema([
                    TextEntry::make('confirmation_code')->label('Booking reference'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('guest.user.name')->label('Guest')->placeholder('—'),
                    TextEntry::make('guest.user.email')->label('Guest email')->placeholder('—'),
                    TextEntry::make('bookable.name')->label('Listing')->placeholder('—'),
                    TextEntry::make('destination.name')->label('Destination')->placeholder('—'),
                    TextEntry::make('check_in_date')->date(),
                    TextEntry::make('check_out_date')->date(),
                    TextEntry::make('guests_adults')->label('Adults'),
                    TextEntry::make('guests_children')->label('Children'),
                    TextEntry::make('total_amount')->money(fn (Booking $record): string => $record->currency),
                    TextEntry::make('payment_status')->badge(),
                    TextEntry::make('payment_method')->placeholder('—'),
                    TextEntry::make('activitySession.starts_at')->label('Activity session')->dateTime()->placeholder('—'),
                    TextEntry::make('special_requests')->state(fn (Booking $record): string => self::format($record->special_requests))->columnSpanFull(),
                    TextEntry::make('created_at')->label('Created')->dateTime(),
                    TextEntry::make('cancelled_at')->label('Cancelled')->dateTime()->placeholder('—'),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['guest.user', 'bookable', 'destination', 'activitySession']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }

    private static function format(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        return is_array($value)
            ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : (string) $value;
    }
}
