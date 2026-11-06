<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\OfferResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Catalog\Domain\Models\Offer;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Provider offers (time-boxed discounts on a listing). Providers create and
 * edit them; an admin reviews them and switches off one that should not run.
 */
class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Offers';

    protected static ?int $navigationSort = 40;

    protected static string|\UnitEnum|null $navigationGroup = 'Content & catalog';

    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (! in_array($action, ['viewAny', 'view'], true)) {
            return Response::deny();
        }

        return auth()->user()?->can('catalog.manage') === true ? Response::allow() : Response::deny();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('bold'),
                TextColumn::make('provider.company_name')->label('Provider')->searchable()->placeholder('—'),
                TextColumn::make('offerable_type')->label('Applies to')
                    ->formatStateUsing(fn (Offer $record): string => self::target($record)),
                TextColumn::make('destination.name')->label('Destination')->placeholder('—'),
                TextColumn::make('discount_percent')->label('Discount')->suffix('%')->sortable(),
                TextColumn::make('start_date')->label('Runs')->date()
                    ->description(fn (Offer $record): string => 'to '.($record->end_date?->toFormattedDateString() ?? 'open')),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                IconColumn::make('active')->boolean()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active'),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                self::toggleAction(),
            ])
            ->emptyStateHeading('No provider offers yet');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Offer')
                ->columns(2)
                ->schema([
                    TextEntry::make('title'),
                    IconEntry::make('active')->boolean(),
                    TextEntry::make('provider.company_name')->label('Provider')->placeholder('—'),
                    TextEntry::make('offerable_type')->label('Applies to')->formatStateUsing(fn (Offer $record): string => self::target($record)),
                    TextEntry::make('destination.name')->label('Destination')->placeholder('—'),
                    TextEntry::make('discount_percent')->label('Discount')->suffix('%'),
                    TextEntry::make('price')->label('Offer price')->numeric(2),
                    TextEntry::make('max_capacity')->placeholder('—'),
                    TextEntry::make('start_date')->dateTime()->placeholder('—'),
                    TextEntry::make('end_date')->dateTime()->placeholder('Open-ended'),
                    IconEntry::make('is_featured')->label('Featured')->boolean(),
                    TextEntry::make('tags')->badge()->placeholder('—'),
                    TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }

    public static function toggleAction(): Action
    {
        return Action::make('toggleActive')
            ->label(fn (Offer $record): string => $record->active ? 'Deactivate' : 'Reactivate')
            ->icon(fn (Offer $record): string => $record->active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
            ->color(fn (Offer $record): string => $record->active ? 'danger' : 'success')
            ->visible(fn (): bool => auth()->user()?->can('catalog.manage') === true)
            ->requiresConfirmation()
            ->schema([Textarea::make('reason')->required()->minLength(5)->maxLength(1000)])
            ->action(function (Offer $record, array $data): void {
                $before = ['active' => $record->active];
                $record->update(['active' => ! $record->active]);

                app(AuditRecorder::class)->record(
                    $record->active ? 'offers.reactivated' : 'offers.deactivated',
                    $record,
                    auth()->user(),
                    before: $before,
                    after: ['active' => $record->active],
                    reason: $data['reason'],
                );

                Notification::make()->success()->title($record->active ? 'Offer reactivated.' : 'Offer deactivated.')->send();
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['provider', 'destination', 'offerable']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffers::route('/'),
            'view' => Pages\ViewOffer::route('/{record}'),
        ];
    }

    private static function target(Offer $record): string
    {
        $type = str($record->offerable_type)->replace(['_', '-'], ' ')->headline()->toString();
        $name = $record->offerable?->name ?? $record->offerable?->title ?? null;

        return $name ? "{$type}: {$name}" : "{$type} #{$record->offerable_id}";
    }
}
