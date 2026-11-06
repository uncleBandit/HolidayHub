<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityOption;
use App\Modules\Administration\Application\Services\BookingLedger;
use App\Modules\Administration\Filament\Resources\BookingOperationsResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Audit\Domain\Models\AuditLog;
use App\Modules\Booking\Application\Services\BookingManager;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Providers\Domain\Models\Provider;
use App\Shared\Support\Currencies;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Every booking on the platform, with the operator actions that change one.
 *
 * Package trips are one parent booking (bookable = the package) plus a child
 * booking per component (package_booking_id = the parent). Children carry a
 * "part of" marker, can be filtered out, and are listed on the parent; the
 * parent's cancel and payment actions carry through to its children.
 *
 * There is no live payment gateway: "mark as paid" and "refund" record what
 * happened elsewhere, they do not move money.
 */
class BookingOperationsResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $recordTitleAttribute = 'confirmation_code';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'booking';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Bookings & money';

    /** Bookings are only made by guests; nothing here creates, edits or deletes one wholesale. */
    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (! in_array($action, ['viewAny', 'view'], true)) {
            return Response::deny();
        }

        return auth()->user()?->can('bookings.view') === true ? Response::allow() : Response::deny();
    }

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
                TextColumn::make('confirmation_code')->label('Reference')->searchable()->sortable()->weight('bold')
                    ->description(fn (Booking $record): ?string => $record->package_booking_id
                        ? '↳ part of '.($record->packageBooking?->confirmation_code ?? '#'.$record->package_booking_id)
                        : ($record->bookable_type === 'package' ? 'Package trip' : null)),
                TextColumn::make('guest.user.name')->label('Guest')->searchable()->placeholder('—')
                    ->description(fn (Booking $record): ?string => $record->guest?->user?->email),
                TextColumn::make('listing')->label('Listing')
                    ->state(fn (Booking $record): string => self::listingName($record))
                    ->description(fn (Booking $record): string => BookingLedger::LISTING_TYPES[$record->bookable_type] ?? $record->bookable_type)
                    ->url(fn (Booking $record): ?string => self::publicUrl($record), shouldOpenInNewTab: true),
                TextColumn::make('provider')->label('Provider / agent')
                    ->state(fn (Booking $record): ?string => self::sellerName($record))
                    ->placeholder('—'),
                TextColumn::make('status')->badge()->sortable()->color(fn (string $state): string => self::statusColor($state)),
                TextColumn::make('payment_status')->label('Payment')->badge()->sortable()->color(fn (string $state): string => self::paymentColor($state)),
                TextColumn::make('check_in_date')->label('Check-in')->date()->sortable(),
                TextColumn::make('check_out_date')->label('Check-out')->date()->sortable(),
                TextColumn::make('total_amount')->label('Total')->sortable()->alignEnd()
                    ->formatStateUsing(fn (Booking $record): string => Currencies::format($record->total_amount, $record->currency, 2)),
                TextColumn::make('agent_commission')->label('Agent commission')->alignEnd()->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (Booking $record): string => Currencies::format($record->agent_commission, $record->currency, 2))
                    ->placeholder('—'),
                TextColumn::make('created_at')->label('Booked')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(BookingLedger::STATUSES)->multiple(),
                SelectFilter::make('payment_status')->label('Payment status')->options(BookingLedger::PAYMENT_STATUSES)->multiple(),
                SelectFilter::make('bookable_type')->label('Listing type')->options(BookingLedger::LISTING_TYPES)->multiple(),
                SelectFilter::make('currency')
                    ->options(fn (): array => Booking::query()->distinct()->orderBy('currency')->pluck('currency', 'currency')->all()),
                SelectFilter::make('package')
                    ->label('Package bookings')
                    ->options([
                        'parents' => 'Package trips only',
                        'components' => 'Package components only',
                        'top' => 'Hide package components',
                        'standalone' => 'Standalone bookings only',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'parents' => $query->where('bookable_type', 'package'),
                        'components' => $query->whereNotNull('package_booking_id'),
                        'top' => $query->whereNull('package_booking_id'),
                        'standalone' => $query->whereNull('package_booking_id')->where('bookable_type', '!=', 'package'),
                        default => $query,
                    }),
                SelectFilter::make('provider')
                    ->label('Provider')
                    ->searchable()
                    ->options(fn (): array => Provider::query()->orderBy('company_name')->pluck('company_name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereRaw(BookingLedger::providerIdSql().' = ?', [(int) $data['value']])
                        : $query),
                self::dateRangeFilter('created_at', 'Booked'),
                self::dateRangeFilter('check_in_date', 'Check-in'),
            ])
            ->filtersFormColumns(2)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                self::cancelAction(),
                self::markPaidAction(),
                self::refundAction(),
                self::overrideAction(),
            ])
            ->toolbarActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => self::exportCsv($livewire->getFilteredSortedTableQuery())),
            ])
            ->emptyStateHeading('No bookings found');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Booking')
                ->columns(3)
                ->schema([
                    TextEntry::make('confirmation_code')->label('Reference')->copyable(),
                    TextEntry::make('status')->badge()->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('created_at')->label('Booked')->dateTime(),
                    TextEntry::make('packageBooking.confirmation_code')->label('Part of package booking')
                        ->url(fn (Booking $record): ?string => $record->package_booking_id ? self::getUrl('view', ['record' => $record->package_booking_id]) : null)
                        ->visible(fn (Booking $record): bool => $record->package_booking_id !== null),
                    TextEntry::make('cancelled_at')->label('Cancelled')->dateTime()->placeholder('—'),
                ]),
            Section::make('Guest')
                ->columns(3)
                ->schema([
                    TextEntry::make('guest.user.name')->label('Name')->placeholder('—'),
                    TextEntry::make('guest.user.email')->label('Email')->placeholder('—')->copyable(),
                    TextEntry::make('guest.phone')->label('Phone')->placeholder('—'),
                ]),
            Section::make('Listing and provider')
                ->columns(3)
                ->schema([
                    TextEntry::make('listing')->label('Listing')
                        ->state(fn (Booking $record): string => self::listingName($record))
                        ->url(fn (Booking $record): ?string => self::publicUrl($record), shouldOpenInNewTab: true)
                        ->color('primary'),
                    TextEntry::make('bookable_type')->label('Type')
                        ->formatStateUsing(fn (string $state): string => BookingLedger::LISTING_TYPES[$state] ?? $state),
                    TextEntry::make('destination.name')->label('Destination')->placeholder('—'),
                    TextEntry::make('seller')->label(fn (Booking $record): string => $record->bookable_type === 'package' ? 'Agent' : 'Provider')
                        ->state(fn (Booking $record): ?string => self::sellerName($record))->placeholder('—'),
                    TextEntry::make('seller_contact')->label('Contact')
                        ->state(fn (Booking $record): ?string => self::sellerContact($record))->placeholder('—'),
                    TextEntry::make('activitySession.starts_at')->label('Experience session')->dateTime()->placeholder('—'),
                ]),
            Section::make('Stay')
                ->columns(4)
                ->schema([
                    TextEntry::make('check_in_date')->label('Check-in')->date(),
                    TextEntry::make('check_out_date')->label('Check-out')->date(),
                    TextEntry::make('nights')->state(fn (Booking $record): int => self::nights($record)),
                    TextEntry::make('guests')->state(fn (Booking $record): string => "{$record->guests_adults} adults, {$record->guests_children} children"),
                ]),
            Section::make(fn (Booking $record): string => "Price ({$record->currency})")
                ->columns(4)
                ->schema([
                    TextEntry::make('price_per_night')->label('Nightly rate')
                        ->formatStateUsing(fn (Booking $record): string => Currencies::format($record->price_per_night, $record->currency, 2))
                        ->placeholder('—'),
                    TextEntry::make('nightly_subtotal')->label('Rate × nights')
                        ->state(fn (Booking $record): ?string => $record->price_per_night
                            ? Currencies::format($record->price_per_night * self::nights($record), $record->currency, 2)
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('adjustments')->label('Fees, offers, seasonal')
                        ->state(fn (Booking $record): ?string => $record->price_per_night
                            ? Currencies::format($record->total_amount - $record->price_per_night * self::nights($record), $record->currency, 2)
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('total_amount')->label('Total charged')->weight('bold')
                        ->formatStateUsing(fn (Booking $record): string => Currencies::format($record->total_amount, $record->currency, 2)),
                    TextEntry::make('agent_commission')->label('Agent commission')
                        ->formatStateUsing(fn (Booking $record): string => Currencies::format($record->agent_commission, $record->currency, 2))
                        ->placeholder('—'),
                    TextEntry::make('components_total')->label('Paid to providers (components)')
                        ->state(fn (Booking $record): ?string => self::componentTotals($record))
                        ->visible(fn (Booking $record): bool => $record->bookable_type === 'package'),
                    TextEntry::make('offer.title')->label('Offer applied')->placeholder('—'),
                ]),
            Section::make('Payment')
                ->columns(3)
                ->schema([
                    TextEntry::make('payment_status')->badge()->color(fn (string $state): string => self::paymentColor($state)),
                    TextEntry::make('payment_method')
                        ->formatStateUsing(fn (?string $state): string => BookingLedger::PAYMENT_METHODS[$state] ?? (string) $state)
                        ->placeholder('—'),
                    TextEntry::make('gateway')->label('Gateway')->state('None connected: payments and refunds are recorded, not processed.'),
                ]),
            Section::make('Package components')
                ->visible(fn (Booking $record): bool => $record->componentBookings->isNotEmpty())
                ->schema([
                    RepeatableEntry::make('componentBookings')
                        ->hiddenLabel()
                        ->columns(6)
                        ->schema([
                            TextEntry::make('confirmation_code')->label('Reference')
                                ->url(fn (Booking $record): string => self::getUrl('view', ['record' => $record])),
                            TextEntry::make('listing')->state(fn (Booking $record): string => self::listingName($record)),
                            TextEntry::make('provider')->state(fn (Booking $record): ?string => self::sellerName($record))->placeholder('—'),
                            TextEntry::make('check_in_date')->label('Dates')
                                ->formatStateUsing(fn (Booking $record): string => $record->check_in_date?->toFormattedDateString().' – '.$record->check_out_date?->toFormattedDateString()),
                            TextEntry::make('status')->badge()->color(fn (string $state): string => self::statusColor($state)),
                            TextEntry::make('total_amount')->label('Amount')
                                ->formatStateUsing(fn (Booking $record): string => Currencies::format($record->total_amount, $record->currency, 2)),
                        ]),
                ]),
            Section::make('Special requests')
                ->collapsed()
                ->schema([
                    TextEntry::make('special_requests')->hiddenLabel()
                        ->state(fn (Booking $record): string => self::format($record->special_requests)),
                ]),
            Section::make('Admin history')
                ->collapsed()
                ->schema([
                    TextEntry::make('admin_history')->hiddenLabel()->listWithLineBreaks()
                        ->state(fn (Booking $record): array => AuditLog::query()
                            ->where('subject_type', $record->getMorphClass())
                            ->where('subject_id', $record->id)
                            ->latest()
                            ->limit(50)
                            ->get(['action', 'reason', 'created_at'])
                            ->map(fn (AuditLog $log): string => $log->created_at?->toDayDateTimeString().' · '.$log->action.($log->reason ? ' · '.$log->reason : ''))
                            ->all())
                        ->placeholder('No admin actions yet.'),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(self::relations());
    }

    /** Everything the table, infolist and actions read, so nothing lazy-loads. */
    public static function relations(): array
    {
        return [
            'guest.user',
            'destination',
            'activitySession',
            'offer',
            'packageBooking:id,confirmation_code',
            'bookable' => fn (MorphTo $morph) => $morph->morphWith(self::bookableRelations()),
            'componentBookings.bookable' => fn (MorphTo $morph) => $morph->morphWith(self::bookableRelations()),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }

    // ── Actions ────────────────────────────────────────────────────────────

    public static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Booking $record): bool => $record->status !== 'cancelled'
                && auth()->user()?->can('bookings.cancel') === true)
            ->requiresConfirmation()
            ->modalHeading(fn (Booking $record): string => "Cancel booking {$record->confirmation_code}")
            ->modalDescription(fn (Booking $record): string => collect([
                'The guest-facing cancellation policy is overridden: an operator may cancel at any time.',
                $record->bookable_type === 'package' ? 'Every component booking of this package is cancelled too.' : null,
                $record->activity_session_id ? 'The experience session places are released.' : null,
                $record->payment_status === 'paid' ? 'No money is returned automatically; use Refund to record a refund.' : null,
            ])->filter()->implode(' '))
            ->schema([Textarea::make('reason')->required()->minLength(10)->maxLength(1000)])
            ->action(function (Booking $record, array $data): void {
                $children = Booking::query()->where('package_booking_id', $record->id)->where('status', '!=', 'cancelled')->get();
                $snapshots = $children->mapWithKeys(fn (Booking $b) => [$b->id => self::snapshot($b)])->all();

                self::runBookingAction($record, 'bookings.cancelled', function (Booking $booking) use ($data): array {
                    $ids = app(BookingManager::class)->cancelAsOperator($booking, $data['reason']);

                    return ['cancelled_booking_ids' => $ids];
                }, $data['reason']);

                foreach ($children as $child) {
                    app(AuditRecorder::class)->record('bookings.cancelled', $child->fresh(), auth()->user(),
                        before: $snapshots[$child->id], after: self::snapshot($child->fresh()), reason: $data['reason'],
                        meta: ['cascaded_from_booking_id' => $record->id]);
                }
            });
    }

    public static function markPaidAction(): Action
    {
        return Action::make('markPaid')
            ->label('Mark as paid')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->visible(fn (Booking $record): bool => in_array($record->payment_status, ['pending', 'failed'], true)
                && $record->status !== 'cancelled'
                && (auth()->user()?->can('bookings.update') || auth()->user()?->can('payments.reconcile')))
            ->requiresConfirmation()
            ->modalDescription('Records a payment received outside the platform. No gateway is charged.')
            ->schema([
                Select::make('payment_method')->options(BookingLedger::PAYMENT_METHODS)->required()
                    ->default(fn (Booking $record): ?string => $record->payment_method),
                Toggle::make('confirm')->label('Also confirm the booking')
                    ->default(fn (Booking $record): bool => $record->status === 'pending')
                    ->visible(fn (Booking $record): bool => $record->status === 'pending'),
                TextInput::make('reference')->label('Payment reference')->maxLength(255),
            ])
            ->action(function (Booking $record, array $data): void {
                self::runBookingAction($record, 'bookings.marked_paid', function (Booking $booking) use ($data): array {
                    $booking->update(array_filter([
                        'payment_status' => 'paid',
                        'payment_method' => $data['payment_method'],
                        'status' => ($data['confirm'] ?? false) && $booking->status === 'pending' ? 'confirmed' : null,
                    ]));

                    // The guest pays the package once; its components are settled by it.
                    $children = Booking::query()->where('package_booking_id', $booking->id)->whereIn('payment_status', ['pending', 'failed'])->pluck('id')->all();
                    Booking::query()->whereKey($children)->update(['payment_status' => 'paid', 'payment_method' => $data['payment_method']]);

                    return ['reference' => $data['reference'] ?? null, 'component_ids_marked_paid' => $children];
                }, $data['reference'] ?? null);
            });
    }

    public static function refundAction(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (Booking $record): bool => $record->payment_status === 'paid'
                && (auth()->user()?->can('bookings.refund') || auth()->user()?->can('payments.refund')))
            ->requiresConfirmation()
            ->modalDescription(fn (Booking $record): string => 'No payment gateway is connected, so this only records the refund: return the money to the guest yourself (M-Pesa, bank, card terminal). '
                .'The booking is marked refunded'.($record->bookable_type === 'package' ? ', with its components' : '').'. Partial amounts are kept in the audit log.')
            ->schema([
                TextInput::make('amount')->label(fn (Booking $record): string => "Amount refunded ({$record->currency})")
                    ->numeric()->minValue(0.01)->maxValue(fn (Booking $record): float => (float) $record->total_amount)
                    ->default(fn (Booking $record): float => (float) $record->total_amount)->required(),
                TextInput::make('reference')->label('Refund reference')->maxLength(255),
                Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
            ])
            ->action(function (Booking $record, array $data): void {
                self::runBookingAction($record, 'bookings.refunded', function (Booking $booking) use ($data): array {
                    $booking->update(['payment_status' => 'refunded']);
                    $children = Booking::query()->where('package_booking_id', $booking->id)->where('payment_status', 'paid')->pluck('id')->all();
                    Booking::query()->whereKey($children)->update(['payment_status' => 'refunded']);

                    return [
                        'amount' => round((float) $data['amount'], 2),
                        'currency' => $booking->currency,
                        'partial' => round((float) $data['amount'], 2) < round((float) $booking->total_amount, 2),
                        'reference' => $data['reference'] ?? null,
                        'gateway' => 'none (recorded manually)',
                        'component_ids_refunded' => $children,
                    ];
                }, $data['reason']);
            });
    }

    public static function overrideAction(): Action
    {
        return Action::make('override')
            ->label('Change dates or status')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->visible(fn (): bool => auth()->user()?->can('bookings.override') === true)
            ->requiresConfirmation()
            ->modalDescription('An override: availability is not re-checked and the price is not recalculated. Cancel with the Cancel action so session places are released.')
            ->fillForm(fn (Booking $record): array => [
                'check_in_date' => $record->check_in_date?->toDateString(),
                'check_out_date' => $record->check_out_date?->toDateString(),
                'status' => $record->status,
            ])
            ->schema([
                DatePicker::make('check_in_date')->label('Check-in')->required()->native(false),
                DatePicker::make('check_out_date')->label('Check-out')->required()->native(false)->afterOrEqual('check_in_date'),
                Select::make('status')->options(collect(BookingLedger::STATUSES)->except('cancelled')->all())->required(),
                Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
            ])
            ->action(function (Booking $record, array $data): void {
                self::runBookingAction($record, 'bookings.overridden', function (Booking $booking) use ($data): array {
                    $booking->update([
                        'check_in_date' => $data['check_in_date'],
                        'check_out_date' => $data['check_out_date'],
                        'status' => $data['status'],
                        // Cancelling is not offered here, so any override leaves the booking live.
                        'cancelled_at' => null,
                    ]);

                    return [];
                }, $data['reason']);
            });
    }

    /**
     * Apply one operator change in a transaction and audit it with the
     * booking's state before and after.
     */
    private static function runBookingAction(Booking $record, string $action, callable $change, ?string $reason): void
    {
        try {
            DB::transaction(function () use ($record, $action, $change, $reason): void {
                $booking = Booking::query()->lockForUpdate()->findOrFail($record->id);
                $before = self::snapshot($booking);
                $meta = $change($booking);

                app(AuditRecorder::class)->record(
                    $action,
                    $booking,
                    auth()->user(),
                    before: $before,
                    after: self::snapshot($booking->fresh()),
                    reason: $reason,
                    meta: array_filter($meta, fn ($value) => $value !== null && $value !== []),
                );
            });
        } catch (\DomainException|\RuntimeException $e) {
            Notification::make()->danger()->title('The booking was not changed.')->body($e->getMessage())->send();

            return;
        }

        $record->refresh();
        Notification::make()->success()->title('Booking '.$record->confirmation_code.' updated.')->send();
    }

    /** @return array<string, mixed> */
    private static function snapshot(Booking $booking): array
    {
        return [
            'status' => $booking->status,
            'payment_status' => $booking->payment_status,
            'payment_method' => $booking->payment_method,
            'check_in_date' => $booking->check_in_date?->toDateString(),
            'check_out_date' => $booking->check_out_date?->toDateString(),
            'cancelled_at' => $booking->cancelled_at?->toIso8601String(),
        ];
    }

    // ── Export ─────────────────────────────────────────────────────────────

    public static function exportCsv(Builder $query): StreamedResponse
    {
        abort_unless(auth()->user()?->can('bookings.view') === true, 403);

        $query = (clone $query)->with(self::relations());

        app(AuditRecorder::class)->record('bookings.exported', null, auth()->user(), meta: ['rows' => (clone $query)->count()]);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            self::csvRow($out, ['reference', 'package_reference', 'status', 'payment_status', 'payment_method', 'listing_type', 'listing', 'provider_or_agent',
                'guest', 'guest_email', 'check_in', 'check_out', 'adults', 'children', 'currency', 'total_amount', 'agent_commission', 'created_at', 'cancelled_at']);

            $query->chunk(500, function ($bookings) use ($out): void {
                foreach ($bookings as $booking) {
                    self::csvRow($out, [
                        $booking->confirmation_code,
                        $booking->packageBooking?->confirmation_code,
                        $booking->status,
                        $booking->payment_status,
                        $booking->payment_method,
                        $booking->bookable_type,
                        self::listingName($booking),
                        self::sellerName($booking),
                        $booking->guest?->user?->name,
                        $booking->guest?->user?->email,
                        $booking->check_in_date?->toDateString(),
                        $booking->check_out_date?->toDateString(),
                        $booking->guests_adults,
                        $booking->guests_children,
                        $booking->currency,
                        number_format((float) $booking->total_amount, 2, '.', ''),
                        $booking->agent_commission !== null ? number_format((float) $booking->agent_commission, 2, '.', '') : '',
                        $booking->created_at?->toIso8601String(),
                        $booking->cancelled_at?->toIso8601String(),
                    ]);
                }
            });

            fclose($out);
        }, 'bookings-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    // ── Presentation helpers ───────────────────────────────────────────────

    /** @return array<class-string, list<string>> */
    private static function bookableRelations(): array
    {
        return [
            Hotel::class => ['provider'],
            Villa::class => ['provider'],
            BedAndBreakfast::class => ['provider'],
            Activity::class => ['provider'],
            ActivityOption::class => ['activity.provider'],
            RoomType::class => ['hotel.provider'],
            Package::class => ['agent'],
        ];
    }

    public static function listingName(Booking $booking): string
    {
        $bookable = $booking->bookable;

        if (! $bookable) {
            return ($booking->bookable_type ?? 'listing').' #'.$booking->bookable_id.' (missing)';
        }

        return (string) ($bookable->getAttribute('name') ?? $bookable->getAttribute('title') ?? '#'.$bookable->getKey());
    }

    public static function publicUrl(Booking $booking): ?string
    {
        $bookable = $booking->bookable;
        $route = match ($booking->bookable_type) {
            'hotel' => 'hotel-show',
            'villa' => 'villa.show',
            'bed_and_breakfast' => 'bedandbreakfast.show',
            'activity', 'experience' => 'activity.show',
            'package' => 'packages-show',
            default => null,
        };

        if (! $route || ! $bookable || blank($bookable->getAttribute('slug'))) {
            return null;
        }

        try {
            return route($route, $bookable->getAttribute('slug'));
        } catch (\Throwable) {
            return null;
        }
    }

    public static function seller(Booking $booking): ?Model
    {
        $bookable = $booking->bookable;

        return match (true) {
            $bookable instanceof Hotel, $bookable instanceof Villa, $bookable instanceof BedAndBreakfast, $bookable instanceof Activity => $bookable->provider,
            $bookable instanceof RoomType => $bookable->hotel?->provider,
            $bookable instanceof ActivityOption => $bookable->activity?->provider,
            $bookable instanceof Package => $bookable->agent,
            default => null,
        };
    }

    public static function sellerName(Booking $booking): ?string
    {
        $seller = self::seller($booking);

        return match (true) {
            $seller instanceof Provider => $seller->company_name,
            $seller !== null => $seller->getAttribute('agency_name')
                ?: trim($seller->getAttribute('first_name').' '.$seller->getAttribute('last_name')),
            default => null,
        };
    }

    private static function sellerContact(Booking $booking): ?string
    {
        $seller = self::seller($booking);

        return $seller ? collect([$seller->getAttribute('email'), $seller->getAttribute('phone')])->filter()->implode(' · ') : null;
    }

    private static function componentTotals(Booking $booking): ?string
    {
        if ($booking->componentBookings->isEmpty()) {
            return null;
        }

        return $booking->componentBookings
            ->where('status', '!=', 'cancelled')
            ->groupBy('currency')
            ->map(fn ($rows, string $currency) => Currencies::format($rows->sum('total_amount'), $currency, 2))
            ->implode(' + ') ?: '—';
    }

    private static function nights(Booking $booking): int
    {
        return $booking->check_in_date && $booking->check_out_date
            ? (int) $booking->check_in_date->diffInDays($booking->check_out_date)
            : 0;
    }

    private static function statusColor(string $state): string
    {
        return match ($state) {
            'confirmed', 'checked_in' => 'success',
            'checked_out' => 'gray',
            'cancelled' => 'danger',
            default => 'warning',
        };
    }

    private static function paymentColor(string $state): string
    {
        return match ($state) {
            'paid' => 'success',
            'refunded' => 'info',
            'failed' => 'danger',
            default => 'warning',
        };
    }

    private static function dateRangeFilter(string $column, string $label): Filter
    {
        return Filter::make($column)
            ->label($label)
            ->schema([
                DatePicker::make('from')->label("{$label} from"),
                DatePicker::make('until')->label("{$label} until"),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate($column, '<=', $date)))
            ->indicateUsing(function (array $data) use ($label): ?string {
                if (! ($data['from'] ?? null) && ! ($data['until'] ?? null)) {
                    return null;
                }

                return $label.': '.($data['from'] ? Carbon::parse($data['from'])->toFormattedDateString() : '…')
                    .' – '.($data['until'] ? Carbon::parse($data['until'])->toFormattedDateString() : '…');
            });
    }

    private static function format(mixed $value): string
    {
        if ($value === null || $value === []) {
            return '—';
        }

        return is_array($value)
            ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : (string) $value;
    }

    /** One CSV line, with the escape character pinned (PHP 8.4 deprecates the default). */
    private static function csvRow($handle, array $fields): void
    {
        fputcsv($handle, $fields, ',', '"', '');
    }
}
