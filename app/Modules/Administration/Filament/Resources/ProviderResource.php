<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Activities\Application\Services\ActivityPublicationService;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Filament\Resources\Concerns\ModeratesBusinessProfiles;
use App\Modules\Administration\Filament\Resources\ProviderResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Audit\Domain\Models\AuditLog;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Providers\Domain\Models\Provider;
use App\Shared\Support\Countries;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Accommodation and experience providers: who they are, what they list, what
 * they earn, and whether they may trade.
 *
 * Suspending a provider turns off `active` (which the media feed already
 * honours) and suspends each of their live listings through the publication
 * services, so guests stop seeing them. The listing IDs are kept in the audit
 * entry, which is what lets reinstatement bring back exactly those listings
 * and not ones a moderator suspended for their own reasons.
 */
class ProviderResource extends Resource
{
    use ModeratesBusinessProfiles;

    /** Booking statuses that represent money the provider has earned. */
    public const EARNING_STATUSES = ['confirmed', 'checked_in', 'checked_out'];

    protected static ?string $model = Provider::class;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Providers';

    protected static ?int $navigationSort = 40;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('providers.view') === true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('providers.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable(['company_name', 'email', 'contact_person'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Provider $record): ?string => $record->email),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->description(fn (Provider $record): ?string => $record->user?->email)
                    ->placeholder('—'),
                IconColumn::make('is_verified')->label('Verified')->boolean()->sortable(),
                TextColumn::make('active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Suspended')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('hotels_count')->label('Hotels')->numeric()->sortable()->alignEnd(),
                TextColumn::make('villas_count')->label('Villas')->numeric()->sortable()->alignEnd(),
                TextColumn::make('bnbs_count')->label('B&Bs')->numeric()->sortable()->alignEnd(),
                TextColumn::make('experiences_count')->label('Experiences')->numeric()->sortable()->alignEnd(),
                TextColumn::make('in_review_count')
                    ->label('In review')
                    ->state(fn (Provider $record): int => (int) $record->stays_in_review_count + (int) $record->experiences_in_review_count)
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('bookings_count')->label('Bookings')->numeric()->sortable()->alignEnd(),
                TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Verified'),
                TernaryFilter::make('suspended')
                    ->label('Suspended')
                    ->queries(
                        true: fn (Builder $query) => $query->where('active', false),
                        false: fn (Builder $query) => $query->where('active', true),
                    ),
                Filter::make('has_listings_in_review')
                    ->label('Has listings in review')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where(fn (Builder $where) => $where
                        ->whereHas('accommodations', fn (Builder $q) => $q->where('status', AccommodationStatus::PendingReview))
                        ->orWhereHas('activities', fn (Builder $q) => $q->whereIn('status', [ActivityStatus::Submitted, ActivityStatus::UnderReview])))),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                ...self::moderationActions(),
            ])
            ->emptyStateHeading('No providers yet');
    }

    /**
     * Verify, unverify, suspend and reinstate. Shared by the table and the view page.
     *
     * @return array<int, Action>
     */
    public static function moderationActions(): array
    {
        return [
            Action::make('verify')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Marks the provider as verified. If they have an open tenant application, it is approved too.')
                ->schema([
                    Textarea::make('reason')->label('Note (optional)')->maxLength(1000)->rows(2),
                ])
                ->visible(fn (Provider $record): bool => ! $record->is_verified
                    && auth()->user()?->can('providers.verify') === true)
                ->action(function (Provider $record, array $data): void {
                    self::setProfileVerification($record, true, $data['reason'] ?? null, 'providers');
                    $record->refresh();
                    Notification::make()->success()->title("{$record->company_name} is now verified.")->send();
                }),
            Action::make('unverify')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                ])
                ->visible(fn (Provider $record): bool => (bool) $record->is_verified
                    && auth()->user()?->can('providers.verify') === true)
                ->action(function (Provider $record, array $data): void {
                    self::setProfileVerification($record, false, $data['reason'], 'providers');
                    $record->refresh();
                    Notification::make()->warning()->title("{$record->company_name} is no longer verified.")->send();
                }),
            Action::make('suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('The provider\'s live stays and experiences are suspended and hidden from guests, and their media profile goes offline.')
                ->schema([
                    Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                ])
                ->visible(fn (Provider $record): bool => (bool) $record->active
                    && auth()->user()?->can('providers.suspend') === true)
                ->action(function (Provider $record, array $data): void {
                    $hidden = self::suspendProvider($record, $data['reason']);
                    $record->refresh();
                    Notification::make()->warning()
                        ->title("{$record->company_name} suspended.")
                        ->body("{$hidden} live listing(s) hidden from guests.")
                        ->send();
                }),
            Action::make('reinstate')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->schema([
                    Toggle::make('restore_listings')
                        ->label('Also reinstate the listings that were suspended with this provider')
                        ->default(true),
                    Textarea::make('reason')->label('Note (optional)')->maxLength(1000)->rows(2),
                ])
                ->visible(fn (Provider $record): bool => ! $record->active
                    && auth()->user()?->can('providers.suspend') === true)
                ->action(function (Provider $record, array $data): void {
                    $restored = self::reinstateProvider($record, (bool) ($data['restore_listings'] ?? false), $data['reason'] ?? null);
                    $record->refresh();
                    Notification::make()->success()
                        ->title("{$record->company_name} reinstated.")
                        ->body("{$restored} listing(s) back in front of guests.")
                        ->send();
                }),
        ];
    }

    /**
     * Take the provider off the marketplace and hide every live listing.
     *
     * @return int how many listings were hidden
     */
    public static function suspendProvider(Provider $provider, string $reason): int
    {
        return DB::transaction(function () use ($provider, $reason): int {
            $locked = Provider::query()->lockForUpdate()->findOrFail($provider->id);
            $before = self::suspensionState($locked);
            $reviewer = (int) auth()->id();
            $listingReason = 'Provider suspended: '.$reason;

            $locked->forceFill([
                'active' => false,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'suspended_by' => auth()->id(),
            ])->save();

            $stayIds = [];
            $accommodations = app(AccommodationPublicationService::class);
            foreach (Accommodation::query()->where('provider_id', $locked->id)->published()->get() as $stay) {
                $accommodations->suspend($stay, $reviewer, $listingReason);
                $stayIds[] = $stay->id;
            }

            $activityIds = [];
            $activities = app(ActivityPublicationService::class);
            foreach (Activity::query()->where('provider_id', $locked->id)->where('status', ActivityStatus::Published)->get() as $activity) {
                $activities->suspend($activity, $reviewer, $listingReason);
                $activityIds[] = $activity->id;
            }

            app(AuditRecorder::class)->record(
                'providers.suspended',
                $locked,
                auth()->user(),
                before: $before,
                after: self::suspensionState($locked),
                reason: $reason,
                meta: ['suspended_accommodation_ids' => $stayIds, 'suspended_activity_ids' => $activityIds],
            );

            return count($stayIds) + count($activityIds);
        });
    }

    /**
     * Put the provider back on the marketplace.
     *
     * @return int how many listings were reinstated
     */
    public static function reinstateProvider(Provider $provider, bool $restoreListings, ?string $reason = null): int
    {
        return DB::transaction(function () use ($provider, $restoreListings, $reason): int {
            $locked = Provider::query()->lockForUpdate()->findOrFail($provider->id);
            $before = self::suspensionState($locked);
            $reviewer = (int) auth()->id();

            $locked->forceFill([
                'active' => true,
                'suspended_at' => null,
                'suspension_reason' => null,
                'suspended_by' => null,
            ])->save();

            $stayIds = [];
            $activityIds = [];

            if ($restoreListings) {
                // Only the listings this provider's suspension took down: the
                // latest suspension audit entry names them.
                $suspension = AuditLog::query()
                    ->where('action', 'providers.suspended')
                    ->where('subject_type', $locked->getMorphClass())
                    ->where('subject_id', $locked->id)
                    ->latest('id')
                    ->first();
                $note = $reason ?: 'Provider reinstated.';

                $accommodations = app(AccommodationPublicationService::class);
                foreach (Accommodation::query()
                    ->where('provider_id', $locked->id)
                    ->whereIn('id', $suspension?->meta['suspended_accommodation_ids'] ?? [])
                    ->where('status', AccommodationStatus::Suspended)
                    ->get() as $stay) {
                    $accommodations->reinstate($stay, $reviewer, $note);
                    $stayIds[] = $stay->id;
                }

                $activities = app(ActivityPublicationService::class);
                foreach (Activity::query()
                    ->where('provider_id', $locked->id)
                    ->whereIn('id', $suspension?->meta['suspended_activity_ids'] ?? [])
                    ->where('status', ActivityStatus::Suspended)
                    ->get() as $activity) {
                    $activities->reinstate($activity, $reviewer, $note);
                    $activityIds[] = $activity->id;
                }
            }

            app(AuditRecorder::class)->record(
                'providers.reinstated',
                $locked,
                auth()->user(),
                before: $before,
                after: self::suspensionState($locked),
                reason: $reason,
                meta: ['reinstated_accommodation_ids' => $stayIds, 'reinstated_activity_ids' => $activityIds],
            );

            return count($stayIds) + count($activityIds);
        });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->columns(3)
                ->schema([
                    TextEntry::make('company_name')->label('Company'),
                    TextEntry::make('provider_type')->label('Type')->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline()->toString() : '—'),
                    TextEntry::make('created_at')->label('Joined')->dateTime(),
                    TextEntry::make('contact_person')->placeholder('—'),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('location')
                        ->state(fn (Provider $record): string => collect([
                            $record->address,
                            $record->city,
                            $record->country ? (Countries::name($record->country) ?? $record->country) : null,
                        ])->filter()->implode(', ') ?: '—')
                        ->columnSpan(2),
                    TextEntry::make('business_license')->label('Business licence')->placeholder('—'),
                    TextEntry::make('tax_number')->placeholder('—'),
                    TextEntry::make('user.name')
                        ->label('Owner account')
                        ->formatStateUsing(fn (Provider $record): string => trim(($record->user?->name ?? '—').' · '.($record->user?->email ?? ''), ' ·'))
                        ->url(fn (Provider $record): ?string => $record->user && UserResource::canViewAny()
                            ? UserResource::getUrl('view', ['record' => $record->user])
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('bio')->columnSpanFull()->placeholder('No bio'),
                ]),
            Section::make('Standing')
                ->columns(3)
                ->schema([
                    IconEntry::make('is_verified')->label('Verified')->boolean(),
                    TextEntry::make('verified_at')->dateTime()->placeholder('—'),
                    TextEntry::make('tenant_status')
                        ->label('Tenant application')
                        ->state(fn (Provider $record): string => Tenant::query()
                            ->where('tenantable_type', $record->getMorphClass())
                            ->where('tenantable_id', $record->id)
                            ->first()?->status?->label() ?? 'None'),
                    TextEntry::make('active')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Suspended')
                        ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    TextEntry::make('suspended_at')->dateTime()->placeholder('—'),
                    TextEntry::make('suspension_reason')->placeholder('—'),
                ]),
            Section::make('Revenue')
                ->description('Confirmed, checked-in and checked-out bookings, per currency. Currencies are never added together.')
                ->schema([
                    ViewEntry::make('revenue')->hiddenLabel()->view('administration::filament.providers.revenue'),
                ]),
            Section::make('Listings')
                ->schema([
                    ViewEntry::make('listings')->hiddenLabel()->view('administration::filament.providers.listings'),
                ]),
            Section::make('Recent bookings')
                ->schema([
                    ViewEntry::make('recent_bookings')->hiddenLabel()->view('administration::filament.providers.bookings'),
                ]),
        ]);
    }

    /**
     * SQL that matches a booking row to the provider whose id is given by
     * $providerIdSql (a column reference or an integer literal).
     *
     * A booking reaches a provider through its bookable (a hotel, room type,
     * villa, B&B or activity) or through an activity session.
     */
    public static function bookingOwnershipSql(string $providerIdSql): string
    {
        return "(
            (bookings.bookable_type = 'hotel' and bookings.bookable_id in (select hotels.id from hotels where hotels.provider_id = {$providerIdSql}))
            or (bookings.bookable_type = 'room_type' and bookings.bookable_id in (select room_types.id from room_types join hotels on hotels.id = room_types.hotel_id where hotels.provider_id = {$providerIdSql}))
            or (bookings.bookable_type = 'villa' and bookings.bookable_id in (select villas.id from villas where villas.provider_id = {$providerIdSql}))
            or (bookings.bookable_type = 'bed_and_breakfast' and bookings.bookable_id in (select bed_and_breakfasts.id from bed_and_breakfasts where bed_and_breakfasts.provider_id = {$providerIdSql}))
            or (bookings.bookable_type = 'activity' and bookings.bookable_id in (select activities.id from activities where activities.provider_id = {$providerIdSql}))
            or (bookings.activity_session_id in (select activity_sessions.id from activity_sessions join activities on activities.id = activity_sessions.activity_id where activities.provider_id = {$providerIdSql}))
        )";
    }

    /**
     * @return Builder<Booking>
     */
    public static function bookingsFor(Provider $provider): Builder
    {
        return Booking::query()->whereRaw(self::bookingOwnershipSql((string) (int) $provider->id));
    }

    /**
     * Earned revenue per currency, largest first.
     *
     * @return Collection<int, object{currency: string, bookings: int, total: string}>
     */
    public static function revenueFor(Provider $provider): Collection
    {
        return self::bookingsFor($provider)
            ->toBase()
            ->whereIn('status', self::EARNING_STATUSES)
            ->selectRaw('currency, count(*) as bookings, sum(total_amount) as total')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->get();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withCount([
                'accommodations as hotels_count' => fn (Builder $q) => $q->where('bookable_type', 'hotel'),
                'accommodations as villas_count' => fn (Builder $q) => $q->where('bookable_type', 'villa'),
                'accommodations as bnbs_count' => fn (Builder $q) => $q->where('bookable_type', 'bed_and_breakfast'),
                'activities as experiences_count',
                'accommodations as stays_in_review_count' => fn (Builder $q) => $q->where('status', AccommodationStatus::PendingReview),
                'activities as experiences_in_review_count' => fn (Builder $q) => $q->whereIn('status', [ActivityStatus::Submitted, ActivityStatus::UnderReview]),
            ])
            ->addSelect(DB::raw('(select count(*) from bookings where '.self::bookingOwnershipSql('providers.id').') as bookings_count'));
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['company_name', 'email'];
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
            'index' => Pages\ListProviders::route('/'),
            'view' => Pages\ViewProvider::route('/{record}'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function suspensionState(Provider $provider): array
    {
        return [
            'active' => (bool) $provider->active,
            'suspended_at' => $provider->suspended_at?->toIso8601String(),
            'suspension_reason' => $provider->suspension_reason,
        ];
    }
}
