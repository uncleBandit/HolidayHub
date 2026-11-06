<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Filament\Resources\AgentResource\Pages;
use App\Modules\Administration\Filament\Resources\Concerns\ModeratesBusinessProfiles;
use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Packages\Domain\Models\Package;
use App\Shared\Support\Countries;
use App\Shared\Support\Currencies;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Travel agents who build packages.
 *
 * Verifying an agent (is_verified + verified_at) is what unlocks package
 * submission for them. Suspension switches off the `active` flag; the agents
 * table has no reason column, so the reason lives in the audit entry.
 */
class AgentResource extends Resource
{
    use ModeratesBusinessProfiles;

    protected static ?string $model = Agent::class;

    protected static ?string $recordTitleAttribute = 'agency_name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Agents';

    protected static ?int $navigationSort = 50;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('agents.view') === true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('agents.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agency_name')
                    ->label('Agency')
                    ->searchable(['agency_name', 'first_name', 'last_name', 'email'])
                    ->sortable()
                    ->weight('bold')
                    ->placeholder('Independent agent')
                    ->description(fn (Agent $record): string => trim($record->first_name.' '.$record->last_name)),
                TextColumn::make('user.name')
                    ->label('User')
                    ->description(fn (Agent $record): ?string => $record->user?->email)
                    ->placeholder('—'),
                IconColumn::make('is_verified')->label('Verified')->boolean()->sortable(),
                TextColumn::make('active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Suspended')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('commission_rate')->label('Commission')->suffix('%')->sortable()->alignEnd(),
                TextColumn::make('packages_count')->label('Packages')->numeric()->sortable()->alignEnd(),
                TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Verified'),
                TernaryFilter::make('active')->label('Active')->trueLabel('Active')->falseLabel('Suspended'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                ...self::moderationActions(),
            ])
            ->emptyStateHeading('No agents yet');
    }

    /**
     * @return array<int, Action>
     */
    public static function moderationActions(): array
    {
        return [
            Action::make('verify')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Verified agents can submit packages for review.')
                ->schema([
                    Textarea::make('reason')->label('Note (optional)')->maxLength(1000)->rows(2),
                ])
                ->visible(fn (Agent $record): bool => ! $record->is_verified
                    && auth()->user()?->can('agents.verify') === true)
                ->action(function (Agent $record, array $data): void {
                    self::setProfileVerification($record, true, $data['reason'] ?? null, 'agents');
                    $record->refresh();
                    Notification::make()->success()->title(self::nameOf($record).' is now verified.')->send();
                }),
            Action::make('unverify')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('The agent can no longer submit packages until verified again.')
                ->schema([
                    Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                ])
                ->visible(fn (Agent $record): bool => (bool) $record->is_verified
                    && auth()->user()?->can('agents.verify') === true)
                ->action(function (Agent $record, array $data): void {
                    self::setProfileVerification($record, false, $data['reason'], 'agents');
                    $record->refresh();
                    Notification::make()->warning()->title(self::nameOf($record).' is no longer verified.')->send();
                }),
            Action::make('suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                ])
                ->visible(fn (Agent $record): bool => (bool) $record->active
                    && auth()->user()?->can('agents.suspend') === true)
                ->action(function (Agent $record, array $data): void {
                    self::setActive($record, false, $data['reason']);
                    $record->refresh();
                    Notification::make()->warning()->title(self::nameOf($record).' suspended.')->send();
                }),
            Action::make('reinstate')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Note (optional)')->maxLength(1000)->rows(2),
                ])
                ->visible(fn (Agent $record): bool => ! $record->active
                    && auth()->user()?->can('agents.suspend') === true)
                ->action(function (Agent $record, array $data): void {
                    self::setActive($record, true, $data['reason'] ?? null);
                    $record->refresh();
                    Notification::make()->success()->title(self::nameOf($record).' reinstated.')->send();
                }),
            Action::make('editCommission')
                ->label('Edit commission')
                ->icon('heroicon-o-receipt-percent')
                ->color('gray')
                ->fillForm(fn (Agent $record): array => ['commission_rate' => $record->commission_rate])
                ->schema([
                    TextInput::make('commission_rate')
                        ->label('Commission rate')
                        ->suffix('%')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->required(),
                    Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                ])
                // No dedicated permission exists; commercial terms are agreed
                // as part of verification, so the verify permission gates it.
                ->visible(fn (): bool => auth()->user()?->can('agents.verify') === true)
                ->action(function (Agent $record, array $data): void {
                    DB::transaction(function () use ($record, $data): void {
                        $locked = Agent::query()->lockForUpdate()->findOrFail($record->id);
                        $before = ['commission_rate' => $locked->commission_rate];
                        $locked->forceFill(['commission_rate' => round((float) $data['commission_rate'], 2)])->save();

                        app(AuditRecorder::class)->record(
                            'agents.commission_updated',
                            $locked,
                            auth()->user(),
                            before: $before,
                            after: ['commission_rate' => $locked->commission_rate],
                            reason: $data['reason'],
                        );
                    });

                    $record->refresh();
                    Notification::make()->success()->title('Commission rate updated.')->send();
                }),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Agent')
                ->columns(3)
                ->schema([
                    TextEntry::make('agency_name')->label('Agency')->placeholder('Independent agent'),
                    TextEntry::make('full_name')->label('Name')->state(fn (Agent $record): string => trim($record->first_name.' '.$record->last_name)),
                    TextEntry::make('business_type')->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline()->toString() : '—')->placeholder('—'),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('agency_license')->label('Licence')->placeholder('—'),
                    TextEntry::make('specialization')->placeholder('—'),
                    TextEntry::make('location')
                        ->state(fn (Agent $record): string => collect([
                            $record->address,
                            $record->city,
                            $record->country ? (Countries::name($record->country) ?? $record->country) : null,
                        ])->filter()->implode(', ') ?: '—')
                        ->columnSpan(2),
                    TextEntry::make('user.name')
                        ->label('User account')
                        ->formatStateUsing(fn (Agent $record): string => trim(($record->user?->name ?? '—').' · '.($record->user?->email ?? ''), ' ·'))
                        ->url(fn (Agent $record): ?string => $record->user && UserResource::canViewAny()
                            ? UserResource::getUrl('view', ['record' => $record->user])
                            : null)
                        ->placeholder('—'),
                    TextEntry::make('created_at')->label('Joined')->dateTime(),
                    TextEntry::make('bio')->columnSpanFull()->placeholder('No bio'),
                ]),
            Section::make('Standing')
                ->columns(4)
                ->schema([
                    IconEntry::make('is_verified')->label('Verified')->boolean(),
                    TextEntry::make('verified_at')->dateTime()->placeholder('—'),
                    TextEntry::make('active')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Suspended')
                        ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    TextEntry::make('commission_rate')->label('Commission')->suffix('%'),
                    TextEntry::make('tenant_status')
                        ->label('Tenant application')
                        ->state(fn (Agent $record): string => Tenant::query()
                            ->where('tenantable_type', $record->getMorphClass())
                            ->where('tenantable_id', $record->id)
                            ->first()?->status?->label() ?? 'None'),
                ]),
            Section::make('Packages')
                ->description('Read-only. Packages are moderated from their own screen.')
                ->schema([
                    RepeatableEntry::make('packages')
                        ->hiddenLabel()
                        ->placeholder('No packages yet')
                        ->schema([
                            TextEntry::make('name')->weight('bold'),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn ($state): string => str($state instanceof \BackedEnum ? $state->value : (string) $state)->headline()->toString()),
                            TextEntry::make('price')
                                ->state(fn (Package $record): string => Currencies::format($record->discount_price ?: $record->base_price, $record->currency, 2)),
                            TextEntry::make('duration')
                                ->state(fn (Package $record): string => ($record->duration_days ?? '—').' days / '.($record->duration_nights ?? '—').' nights'),
                            TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                            TextEntry::make('published_at')->dateTime()->placeholder('—'),
                        ])
                        ->columns(6),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withCount('packages');
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
            'index' => Pages\ListAgents::route('/'),
            'view' => Pages\ViewAgent::route('/{record}'),
        ];
    }

    private static function setActive(Agent $agent, bool $active, ?string $reason): void
    {
        DB::transaction(function () use ($agent, $active, $reason): void {
            $locked = Agent::query()->lockForUpdate()->findOrFail($agent->id);
            $before = ['active' => (bool) $locked->active];
            $locked->forceFill(['active' => $active])->save();

            app(AuditRecorder::class)->record(
                $active ? 'agents.reinstated' : 'agents.suspended',
                $locked,
                auth()->user(),
                before: $before,
                after: ['active' => $active],
                reason: $reason,
            );
        });
    }

    private static function nameOf(Agent $agent): string
    {
        return $agent->agency_name ?: trim($agent->first_name.' '.$agent->last_name);
    }
}
