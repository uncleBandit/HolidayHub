<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Administration\Filament\Resources\AccommodationModerationResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccommodationModerationResource extends Resource
{
    protected static ?string $model = Accommodation::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Accommodation moderation';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('accommodations.view') === true;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('accommodations.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('provider.company_name')->label('Provider')->searchable()->placeholder('—'),
                TextColumn::make('destination.name')->label('Destination')->sortable()->placeholder('—'),
                TextColumn::make('bookable_type')->label('Type')->formatStateUsing(
                    fn (?string $state): string => $state ? str(class_basename($state))->headline()->toString() : '—'
                ),
                TextColumn::make('status')->badge(),
                TextColumn::make('submitted_at')->label('Submitted')->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(AccommodationStatus::cases())->mapWithKeys(
                    fn (AccommodationStatus $status): array => [$status->value => str($status->value)->headline()->toString()]
                )->all()),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve and publish')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Accommodation $record): bool => $record->status === AccommodationStatus::PendingReview
                        && auth()->user()?->can('approve', $record) === true)
                    ->action(fn (Accommodation $record) => self::runTransition(
                        $record,
                        'accommodations.published',
                        fn (AccommodationPublicationService $service, Accommodation $model) => $service->approve($model, auth()->id()),
                    )),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                    ])
                    ->visible(fn (Accommodation $record): bool => $record->status === AccommodationStatus::PendingReview
                        && auth()->user()?->can('reject', $record) === true)
                    ->action(fn (Accommodation $record, array $data) => self::runTransition(
                        $record,
                        'accommodations.rejected',
                        fn (AccommodationPublicationService $service, Accommodation $model) => $service->reject($model, auth()->id(), $data['reason']),
                        $data['reason'],
                    )),
                Action::make('suspend')
                    ->color('warning')
                    ->icon('heroicon-o-pause-circle')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                    ])
                    ->visible(fn (Accommodation $record): bool => $record->status === AccommodationStatus::Published
                        && auth()->user()?->can('suspend', $record) === true)
                    ->action(fn (Accommodation $record, array $data) => self::runTransition(
                        $record,
                        'accommodations.suspended',
                        fn (AccommodationPublicationService $service, Accommodation $model) => $service->suspend($model, auth()->id(), $data['reason']),
                        $data['reason'],
                    )),
            ])
            ->emptyStateHeading('No accommodation listings need administration');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Listing')
                ->columns(2)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('provider.company_name')->label('Provider')->placeholder('—'),
                    TextEntry::make('destination.name')->label('Destination')->placeholder('—'),
                    TextEntry::make('city'),
                    TextEntry::make('country'),
                    TextEntry::make('address')->columnSpanFull(),
                    TextEntry::make('description')->columnSpanFull(),
                    TextEntry::make('policies')->state(fn (Accommodation $record): string => self::format($record->policies))->columnSpanFull(),
                ]),
            Section::make('Verification history')
                ->schema([
                    RepeatableEntry::make('verificationHistory')
                        ->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('status'),
                            TextEntry::make('reviewer.name')->label('Reviewer')->placeholder('—'),
                            TextEntry::make('reason')->columnSpanFull()->placeholder('—'),
                        ])->columns(3),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['provider', 'destination', 'bookable', 'verificationHistory.reviewer']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccommodations::route('/'),
            'view' => Pages\ViewAccommodation::route('/{record}'),
        ];
    }

    private static function runTransition(
        Accommodation $record,
        string $action,
        callable $transition,
        ?string $reason = null,
    ): void {
        DB::transaction(function () use ($record, $action, $transition, $reason): void {
            $before = ['status' => $record->status?->value];
            $updated = $transition(app(AccommodationPublicationService::class), $record->fresh());

            app(AuditRecorder::class)->record(
                $action,
                $updated,
                auth()->user(),
                before: $before,
                after: ['status' => $updated->status?->value],
                reason: $reason,
            );
        });

        Notification::make()->success()->title('Accommodation moderation decision recorded.')->send();
    }

    private static function format(?array $value): string
    {
        return $value === null ? '—' : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
