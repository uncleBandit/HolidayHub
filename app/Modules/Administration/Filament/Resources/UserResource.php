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
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

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
                    ->placeholder('No role')
                    ->separator(','),

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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->indicator('Role'),
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
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $revoked = 0;

                        foreach ($records as $user) {
                            $revoked += $user->tokens()->delete();
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
                    ->relationship('roles', 'name')
                    ->multiple
                    ->preload()
                    ->visible(fn (?User $record): bool => $record !== null
                        && auth()->user()?->can('manageRoles', $record) === true)
                    ->dehydrated(fn (?User $record): bool => $record !== null
                        && auth()->user()?->can('manageRoles', $record) === true)
                    ->helperText('Roles grant only their explicitly assigned permissions. Super-admin access is restricted.'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('roles.name')->badge()->placeholder('No role'),
                TextEntry::make('email_verified_at')
                    ->label('Email verified')
                    ->dateTime()
                    ->placeholder('Unverified'),
                TextEntry::make('created_at')->label('Joined')->dateTime(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('roles');
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
}
