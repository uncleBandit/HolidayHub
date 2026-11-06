<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\UserResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Identity\Domain\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Platform account administration: see who is on the platform, what role they
 * hold, and take away access when an account is abused.
 *
 * This is a read-mostly oversight screen. It deliberately does not expose
 * password hashes, and it does not delete users: bookings, reviews and payouts
 * reference them, so an account is deactivated instead.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Accounts';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'People';

    protected static ?string $modelLabel = 'account';

    protected static ?string $pluralModelLabel = 'accounts';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::roleLabel($state))
                    ->placeholder('No role')
                    ->separator(','),

                TextColumn::make('business_profile')
                    ->label('Business profile')
                    ->state(fn (User $record): array => array_values(array_filter([
                        $record->provider ? 'Provider' : null,
                        $record->agent ? 'Agent' : null,
                    ])))
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('email_verified_at')
                    ->label('Email')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state === null ? 'Unverified' : 'Verified')
                    ->color(fn ($state): string => $state === null ? 'warning' : 'success')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
                TextColumn::make('suspended_at')
                    ->label('Account')
                    ->state(fn (User $record): string => $record->isSuspended() ? 'Suspended' : 'Active')
                    ->badge()
                    ->color(fn (User $record): string => $record->isSuspended() ? 'danger' : 'success')
                    ->tooltip(fn (User $record): ?string => $record->suspension_reason),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => self::roleLabel($record->name))
                    ->indicator('Role'),
                \Filament\Tables\Filters\TernaryFilter::make('suspended')
                    ->label('Suspended')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('suspended_at'),
                        false: fn (Builder $query) => $query->whereNull('suspended_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),

                EditAction::make()
                    // An admin must not be able to edit themselves out of the
                    // admin role and lock the platform out of its own panel.
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) === true),

                Action::make('toggleEmailVerification')
                    ->visible(fn (User $record): bool => auth()->user()?->can('manageEmailVerification', $record) === true)
                    ->label(fn (User $record): string => $record->email_verified_at === null
                        ? 'Mark email verified'
                        : 'Revoke email verification')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        // Read the state before writing. Checking after the save
                        // reports the opposite of what was just done, because the
                        // column already holds the new value.
                        $wasVerified = $record->email_verified_at !== null;

                        $record->forceFill([
                            'email_verified_at' => $wasVerified ? null : now(),
                        ])->save();
                        app(AuditRecorder::class)->record(
                            $wasVerified ? 'users.email_verification_revoked' : 'users.email_verified',
                            $record,
                            auth()->user(),
                            before: ['email_verified' => $wasVerified],
                            after: ['email_verified' => ! $wasVerified],
                        );

                        Notification::make()
                            ->success()
                            ->title($wasVerified
                                ? 'Email verification revoked.'
                                : 'Email marked as verified.')
                            ->send();
                    }),

                ...self::accountActions(),

                Action::make('revokeApiTokens')
                    ->visible(fn (User $record): bool => auth()->user()?->can('revokeTokens', $record) === true)
                    ->label('Revoke API tokens')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    // A compromised account keeps its mobile API access until its
                    // Sanctum tokens are destroyed, so deactivating the user alone
                    // is not enough to lock them out.
                    ->action(function (User $record): void {
                        $revoked = $record->tokens()->delete();
                        app(AuditRecorder::class)->record(
                            'users.api_tokens_revoked',
                            $record,
                            auth()->user(),
                            after: ['revoked_count' => $revoked],
                        );

                        Notification::make()
                            ->warning()
                            ->title("{$revoked} API token(s) revoked.")
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('revokeTokens')
                    ->label('Revoke API tokens')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->can('users.tokens.revoke') === true)
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $revoked = 0;

                        foreach ($records as $user) {
                            // Same rule as the row action: never your own account.
                            if (auth()->user()?->can('revokeTokens', $user) !== true) {
                                continue;
                            }

                            $count = $user->tokens()->delete();
                            $revoked += $count;
                            app(AuditRecorder::class)->record(
                                'users.api_tokens_revoked',
                                $user,
                                auth()->user(),
                                after: ['revoked_count' => $count],
                                meta: ['bulk' => true],
                            );
                        }

                        Notification::make()
                            ->warning()
                            ->title("{$revoked} API token(s) revoked.")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('No accounts');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    // Email is the login identifier, so changing it is a
                    // privileged edit that must not silently collide.
                    ->unique(ignoreRecord: true),

                Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name', fn (Builder $query) => $query->orderBy('name'))
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => self::roleLabel($record->name))
                    ->multiple()
                    ->preload()
                    ->visible(fn (?User $record): bool => $record !== null
                        && auth()->user()?->can('manageRoles', $record) === true)
                    ->dehydrated(fn (?User $record): bool => $record !== null
                        && auth()->user()?->can('manageRoles', $record) === true)
                    ->helperText('Operational roles grant admin-panel access with only their assigned permissions. Guest, Provider and Agent are marketplace roles. Super admin is unrestricted.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => self::roleLabel($state))
                            ->placeholder('No role'),
                        TextEntry::make('email_verified_at')
                            ->label('Email verified')
                            ->dateTime()
                            ->placeholder('Unverified'),
                        TextEntry::make('created_at')->label('Joined')->dateTime(),
                        TextEntry::make('suspended_at')
                            ->label('Suspended')
                            ->dateTime()
                            ->color('danger')
                            ->visible(fn (User $record): bool => $record->isSuspended()),
                        TextEntry::make('suspension_reason')
                            ->label('Suspension reason')
                            ->color('danger')
                            ->columnSpan(2)
                            ->visible(fn (User $record): bool => $record->isSuspended()),
                        TextEntry::make('google_id')
                            ->label('Google sign-in')
                            ->formatStateUsing(fn (): string => 'Linked')
                            ->placeholder('Not linked'),
                    ]),
                Section::make('Business profiles')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('provider.company_name')
                            ->label('Provider profile')
                            ->formatStateUsing(fn (User $record): string => $record->provider->company_name
                                .($record->provider->active ? '' : ' (suspended)')
                                .($record->provider->is_verified ? ' · verified' : ' · unverified'))
                            ->url(fn (User $record): ?string => $record->provider && ProviderResource::canViewAny()
                                ? ProviderResource::getUrl('view', ['record' => $record->provider])
                                : null)
                            ->placeholder('None'),
                        TextEntry::make('agent.id')
                            ->label('Agent profile')
                            ->formatStateUsing(fn (User $record): string => ($record->agent->agency_name ?: trim($record->agent->first_name.' '.$record->agent->last_name))
                                .($record->agent->active ? '' : ' (suspended)')
                                .($record->agent->is_verified ? ' · verified' : ' · unverified'))
                            ->url(fn (User $record): ?string => $record->agent && AgentResource::canViewAny()
                                ? AgentResource::getUrl('view', ['record' => $record->agent])
                                : null)
                            ->placeholder('None'),
                    ]),
                Section::make('Bookings as a guest')
                    ->description('The 10 most recent.')
                    ->schema([
                        ViewEntry::make('guest_bookings')->hiddenLabel()->view('administration::filament.users.bookings'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['roles', 'provider', 'agent']);
    }

    /**
     * "operations_manager" -> "Operations manager".
     */
    public static function roleLabel(string $name): string
    {
        return str($name)->replace('_', ' ')->ucfirst()->toString();
    }

    /**
     * @return array<int, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Suspend and restore, shared by the table and the user's page.
     *
     * @return array<int, Action>
     */
    public static function accountActions(): array
    {
        return [
            Action::make('suspend')
                ->label('Suspend account')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (User $record): bool => self::canSuspend($record) && ! $record->isSuspended())
                ->modalDescription('They are signed out everywhere, cannot sign in again and lose their API tokens until the account is restored. Their listings and bookings are left as they are.')
                ->schema([
                    Textarea::make('reason')->label('Why is this account being suspended?')->required()->minLength(10)->maxLength(1000)->rows(3),
                ])
                ->action(function (User $record, array $data): void {
                    $revoked = $record->tokens()->delete();
                    $record->forceFill([
                        'suspended_at' => now(),
                        'suspension_reason' => trim($data['reason']),
                        'suspended_by' => auth()->id(),
                        // A new remember token makes any "remember me" cookie useless.
                        'remember_token' => Str::random(60),
                    ])->save();

                    app(AuditRecorder::class)->record(
                        'users.suspended',
                        $record,
                        auth()->user(),
                        before: ['suspended' => false],
                        after: ['suspended' => true, 'api_tokens_revoked' => $revoked],
                        reason: $data['reason'],
                    );

                    Notification::make()->success()->title("{$record->name} is suspended.")->send();
                }),

            Action::make('restore')
                ->label('Restore account')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription(fn (User $record): string => 'Suspended '.$record->suspended_at?->diffForHumans().': “'.$record->suspension_reason.'”. They will be able to sign in again.')
                ->visible(fn (User $record): bool => $record->isSuspended() && auth()->user()?->can('users.restore') === true)
                ->action(function (User $record): void {
                    $before = ['suspended' => true, 'reason' => $record->suspension_reason];
                    $record->forceFill(['suspended_at' => null, 'suspension_reason' => null, 'suspended_by' => null])->save();

                    app(AuditRecorder::class)->record('users.restored', $record, auth()->user(), before: $before, after: ['suspended' => false]);

                    Notification::make()->success()->title("{$record->name} can sign in again.")->send();
                }),
        ];
    }

    /**
     * Who may suspend whom: never yourself (you would lock yourself out), and
     * only a super admin may suspend another super admin.
     */
    private static function canSuspend(User $record): bool
    {
        $actor = auth()->user();

        return $actor?->can('users.suspend') === true
            && $actor->getKey() !== $record->getKey()
            && (! $record->hasRole('super_admin') || $actor->hasRole('super_admin'));
    }
}
