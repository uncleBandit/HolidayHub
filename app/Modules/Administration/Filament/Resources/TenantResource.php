<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Filament\Resources\TenantResource\Pages;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

/**
 * The tenant review queue: the screen an admin uses to verify the people and
 * businesses applying to sell services on the platform.
 *
 * Every decision routes through TenantVerificationService rather than writing
 * the tenant row directly, so the workflow rules, the decision history and the
 * audit trail cannot be bypassed from the UI.
 */
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenant verification';

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $modelLabel = 'tenant';

    protected static ?string $pluralModelLabel = 'tenants';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Business')
                    ->searchable(['display_name', 'contact_email'])
                    ->description(fn (Tenant $record): ?string => $record->contact_email)
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TenantStatus $state): string => $state->label())
                    ->color(fn (TenantStatus $state): ?string => $state->color()),

                TextColumn::make('submitted_at')
                    ->label('Applied')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('verifier.name')
                    ->label('Reviewed by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Oldest application first, so the longest-waiting tenant is never
            // buried beneath a stream of new ones.
            ->defaultSort('created_at', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TenantStatus::options())
                    ->indicator(TenantStatus::Pending->label()),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'provider' => 'Provider',
                        'agent' => 'Agent',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('startReview')
                    ->label('Start review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    // Claiming work in a shared queue: only offered once, and
                    // withdrawn the moment the status moves on.
                    ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::Pending
                        && auth()->user()?->can('decide', $record) === true)
                    ->action(function (Tenant $record): void {
                        app(TenantVerificationService::class)
                            ->startReview($record, auth()->user());

                        self::notifyMoved($record, 'is now under review');
                    }),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->schema([
                        Textarea::make('note')
                            ->label('Internal note')
                            ->helperText('Optional. Not shown to the tenant.')
                            ->rows(2),
                    ])
                    ->modalHeading('Approve this tenant?')
                    ->modalDescription('The tenant will be able to list services on the platform immediately.')
                    ->modalSubmitActionLabel('Approve')
                    ->visible(fn (Tenant $record): bool => auth()->user()?->can('approve', $record) === true)
                    ->action(function (Tenant $record, array $data): void {
                        app(TenantVerificationService::class)
                            ->approve($record, auth()->user(), $data['note'] ?? null);

                        self::notifyMoved($record, 'was approved');
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for rejection')
                            ->helperText('Shown to the tenant so they can correct and resubmit.')
                            ->rows(3)
                            ->required(),
                    ])
                    ->modalHeading('Reject this application?')
                    ->modalSubmitActionLabel('Reject')
                    ->visible(fn (Tenant $record): bool => auth()->user()?->can('reject', $record) === true)
                    ->action(function (Tenant $record, array $data): void {
                        app(TenantVerificationService::class)
                            ->reject($record, auth()->user(), $data['reason']);

                        self::notifyMoved($record, 'was rejected');
                    }),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for suspension')
                            ->helperText('Shown to the tenant. Their listings are hidden until reinstated.')
                            ->rows(3)
                            ->required(),
                    ])
                    ->visible(fn (Tenant $record): bool => auth()->user()?->can('suspend', $record) === true)
                    ->action(function (Tenant $record, array $data): void {
                        app(TenantVerificationService::class)
                            ->suspend($record, auth()->user(), $data['reason']);

                        self::notifyMoved($record, 'was suspended');
                    }),

                Action::make('reinstate')
                    ->label('Reinstate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->schema([
                        Textarea::make('note')
                            ->label('Internal note')
                            ->rows(2),
                    ])
                    ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::Suspended
                        && auth()->user()?->can('restore', $record) === true)
                    ->action(function (Tenant $record, array $data): void {
                        app(TenantVerificationService::class)
                            ->reinstate($record, auth()->user(), $data['note'] ?? null);

                        self::notifyMoved($record, 'was reinstated');
                    }),

                Action::make('reopen')
                    ->label('Return to queue')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status === TenantStatus::Rejected
                        && auth()->user()?->can('restore', $record) === true)
                    ->action(function (Tenant $record): void {
                        app(TenantVerificationService::class)
                            ->reopen($record, auth()->user());

                        self::notifyMoved($record, 'was returned to the review queue');
                    }),
            ])
            ->bulkActions([
                BulkAction::make('approveSelected')
                    ->label('Approve selected')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $service = app(TenantVerificationService::class);
                        $approved = 0;
                        $skipped = 0;

                        foreach ($records as $tenant) {
                            // Individual failures are counted rather than
                            // aborting the batch, so one already-decided row
                            // cannot block the rest of an admin's selection.
                            try {
                                $service->approve($tenant, auth()->user());
                                $approved++;
                            } catch (\DomainException) {
                                $skipped++;
                            }
                        }

                        self::reportBulk($approved, $skipped, 'approved');
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('suspendSelected')
                    ->label('Suspend selected')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for suspension')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                        $service = app(TenantVerificationService::class);
                        $suspended = 0;
                        $skipped = 0;

                        foreach ($records as $tenant) {
                            try {
                                $service->suspend($tenant, auth()->user(), $data['reason']);
                                $suspended++;
                            } catch (\DomainException) {
                                $skipped++;
                            }
                        }

                        self::reportBulk($suspended, $skipped, 'suspended');
                    }),
            ])
            ->emptyStateHeading('No tenants yet')
            ->emptyStateDescription('Applications to sell services on the platform will appear here.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Application')
                    ->schema([
                        TextEntry::make('display_name')->label('Business'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
                        TextEntry::make('contact_email')->label('Contact email'),
                        TextEntry::make('user.name')->label('Account holder'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (TenantStatus $state): string => $state->label())
                            ->color(fn (TenantStatus $state): ?string => $state->color()),
                    ])
                    ->columns(2),

                Section::make('Decision')
                    ->schema([
                        TextEntry::make('submitted_at')->label('Applied at')->dateTime()->placeholder('—'),
                        TextEntry::make('verified_at')->label('Approved at')->dateTime()->placeholder('—'),
                        TextEntry::make('verifier.name')->label('Approved by')->placeholder('—'),
                        TextEntry::make('suspended_at')->label('Suspended at')->dateTime()->placeholder('—'),
                        TextEntry::make('rejection_reason')
                            ->label('Rejection reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('suspension_reason')
                            ->label('Suspension reason')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('admin_notes')
                            ->label('Internal notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (Tenant $record): bool => $record->status !== TenantStatus::Pending),

                Section::make('Business profile')
                    ->schema([
                        TextEntry::make('tenantable_type')
                            ->label('Profile type')
                            ->formatStateUsing(fn (?string $state): string => $state === null
                                ? 'Not linked'
                                : class_basename($state)),
                        TextEntry::make('tenantable_id')
                            ->label('Profile reference')
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Section::make('Verification history')
                    ->description('Every recorded decision, oldest first.')
                    ->schema([
                        RepeatableEntry::make('verifications')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('created_at')->label('When')->dateTime(),
                                TextEntry::make('from_status')
                                    ->label('From')
                                    ->placeholder('—')
                                    ->formatStateUsing(fn (?TenantStatus $state): string => $state?->label() ?? '—'),
                                TextEntry::make('to_status')
                                    ->label('To')
                                    ->formatStateUsing(fn (TenantStatus $state): string => $state->label()),
                                TextEntry::make('admin.name')->label('By')->placeholder('Tenant'),
                                TextEntry::make('reason')->label('Reason')->placeholder('—'),
                            ]),
                    ]),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('display_name')
                    ->required()
                    ->maxLength(255),

                Select::make('type')
                    ->required()
                    ->options([
                        'provider' => 'Provider',
                        'agent' => 'Agent',
                    ]),

                TextInput::make('contact_email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                DateTimePicker::make('submitted_at')
                    ->label('Applied at')
                    ->seconds(false),

                Textarea::make('admin_notes')
                    ->label('Internal notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'verifier']);
    }

    /**
     * Surface a workflow rule violation instead of a 500.
     *
     * The service throws DomainException for illegal transitions; if a decision
     * is attempted twice — two admins, two tabs, or a stale page — the admin
     * gets an explanation rather than an error page.
     */
    protected static function notifyMoved(Tenant $record, string $verb): void
    {
        Notification::make()
            ->success()
            ->title("{$record->display_name} {$verb}.")
            ->body('The decision has been recorded in the verification history.')
            ->send();
    }

    protected static function reportBulk(int $succeeded, int $skipped, string $verb): void
    {
        $notification = Notification::make()
            ->title("{$succeeded} tenant(s) {$verb}.")
            ->success();

        if ($skipped > 0) {
            $notification = $notification
                ->warning()
                ->body("{$skipped} skipped: already decided or not eligible for this action.");
        }

        $notification->send();
    }

    /**
     * @return array<int, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }
}
