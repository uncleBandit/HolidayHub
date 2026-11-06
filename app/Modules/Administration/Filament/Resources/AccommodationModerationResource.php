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
use Filament\Infolists\Components\ViewEntry;
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

    protected static ?string $navigationLabel = 'Stays';

    protected static ?string $modelLabel = 'stay';

    protected static ?string $pluralModelLabel = 'stays';

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

    public static function getNavigationBadge(): ?string
    {
        $waiting = Accommodation::query()->where('status', AccommodationStatus::PendingReview)->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Awaiting review';
    }

    /**
     * Where guests see this stay. Null when the property record is missing.
     */
    public static function publicUrl(Accommodation $record): ?string
    {
        if (blank($record->slug)) {
            return null;
        }

        return match ($record->bookable_type) {
            'hotel' => route('hotel-show', $record->slug),
            'villa' => route('villa.show', $record->slug),
            'bed_and_breakfast' => route('bedandbreakfast.show', $record->slug),
            default => null,
        };
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
                ...self::moderationActions(),
            ])
            ->emptyStateHeading('No accommodation listings need administration');
    }

    /**
     * Approve, reject, suspend and reinstate. Shared by the table and the view page.
     *
     * @return array<int, Action>
     */
    public static function moderationActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve and publish')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Accommodation $record): bool => $record->status === AccommodationStatus::PendingReview
                    && ! self::providerSuspended($record)
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
                    Textarea::make('reason')
                        ->helperText('Recorded on the listing and its decision history for the host.')
                        ->required()->minLength(10)->maxLength(1000),
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
            Action::make('reinstate')
                ->color('success')
                ->icon('heroicon-o-play-circle')
                ->requiresConfirmation()
                ->modalDescription('The stay goes back in front of guests without a fresh review.')
                ->schema([
                    Textarea::make('reason')->label('Note (optional)')->maxLength(1000)->rows(2),
                ])
                ->visible(fn (Accommodation $record): bool => $record->status === AccommodationStatus::Suspended
                    && ! self::providerSuspended($record)
                    && auth()->user()?->can('suspend', $record) === true)
                ->action(fn (Accommodation $record, array $data) => self::runTransition(
                    $record,
                    'accommodations.reinstated',
                    fn (AccommodationPublicationService $service, Accommodation $model) => $service->reinstate($model, auth()->id(), $data['reason'] ?? null),
                    $data['reason'] ?? null,
                )),
        ];
    }

    /**
     * A suspended provider's listings stay hidden until the provider is reinstated.
     */
    private static function providerSuspended(Accommodation $record): bool
    {
        return $record->provider !== null && ! $record->provider->active;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Listing')
                ->columns(2)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('bookable_type')->label('Type')->formatStateUsing(
                        fn (?string $state): string => $state ? str($state)->headline()->toString() : '—'
                    ),
                    TextEntry::make('provider.company_name')
                        ->label('Provider')
                        ->url(fn (Accommodation $record): ?string => $record->provider && ProviderResource::canViewAny()
                            ? ProviderResource::getUrl('view', ['record' => $record->provider])
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('destination.name')->label('Destination')->placeholder('—'),
                    TextEntry::make('public_page')
                        ->label('Public page')
                        ->state(fn (Accommodation $record): ?string => self::publicUrl($record))
                        ->formatStateUsing(fn (Accommodation $record): string => $record->isPublished() ? 'View public page' : 'View public page (not live)')
                        ->url(fn (Accommodation $record): ?string => self::publicUrl($record), shouldOpenInNewTab: true)
                        ->placeholder('—'),
                    TextEntry::make('city'),
                    TextEntry::make('country'),
                    TextEntry::make('submitted_at')->label('Submitted')->dateTime()->placeholder('—'),
                    TextEntry::make('suspension_reason')
                        ->label(fn (Accommodation $record): string => $record->status === AccommodationStatus::Rejected ? 'Rejection reason' : 'Suspension reason')
                        ->visible(fn (Accommodation $record): bool => filled($record->suspension_reason)),
                    TextEntry::make('address')->columnSpanFull(),
                    TextEntry::make('description')->columnSpanFull(),
                    TextEntry::make('policies')->state(fn (Accommodation $record): string => self::format($record->policies))->columnSpanFull(),
                ]),
            Section::make('Media')
                ->description('Cover, gallery and the reel walk in the order guests see it.')
                ->collapsible()
                ->schema([
                    ViewEntry::make('media_preview')->hiddenLabel()->view('administration::filament.moderation.media-preview'),
                ]),
            Section::make('Property details')
                ->collapsible()
                ->schema([
                    ViewEntry::make('stay_details')->hiddenLabel()->view('administration::filament.moderation.stay-details'),
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
        $updated = DB::transaction(function () use ($record, $action, $transition, $reason) {
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

            return $updated;
        });

        // Keep the on-screen record (and its loaded media) in step with the decision.
        $record->setRawAttributes($updated->getAttributes(), true);

        Notification::make()->success()->title('Accommodation moderation decision recorded.')->send();
    }

    private static function format(?array $value): string
    {
        return $value === null ? '—' : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
