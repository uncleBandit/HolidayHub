<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Activities\Application\Services\ActivityPublicationService;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Filament\Resources\ActivityModerationResource\Pages;
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

class ActivityModerationResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Activity moderation';

    protected static ?int $navigationSort = 30;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('activities.view') === true;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('activities.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('provider.company_name')->label('Provider')->searchable()->placeholder('—'),
                TextColumn::make('destination.name')->label('Destination')->sortable()->placeholder('—'),
                TextColumn::make('category.name')->label('Category')->sortable()->placeholder('—'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (?ActivityStatus $state): string => $state?->value ?? '—'),
                TextColumn::make('submitted_at')->label('Submitted')->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(ActivityStatus::cases())->mapWithKeys(
                    fn (ActivityStatus $status): array => [$status->value => str($status->value)->headline()->toString()]
                )->all()),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->recordActions([
                ViewAction::make(),
                Action::make('startReview')
                    ->label('Start review')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (Activity $record): bool => $record->status === ActivityStatus::Submitted
                        && auth()->user()?->can('moderate', Activity::class) === true)
                    ->action(fn (Activity $record) => self::runTransition(
                        $record,
                        'activities.review_started',
                        fn (ActivityPublicationService $service, Activity $activity) => $service->markUnderReview($activity, auth()->id()),
                    )),
                Action::make('approve')
                    ->label('Approve and publish')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Activity $record): bool => in_array($record->status, [ActivityStatus::Submitted, ActivityStatus::UnderReview], true)
                        && auth()->user()?->can('approve', Activity::class) === true)
                    ->action(fn (Activity $record) => self::runTransition(
                        $record,
                        'activities.published',
                        function (ActivityPublicationService $service, Activity $activity): Activity {
                            $approved = $service->approve($activity, auth()->id());

                            return $service->publish($approved);
                        },
                    )),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                    ])
                    ->visible(fn (Activity $record): bool => in_array($record->status, [ActivityStatus::Submitted, ActivityStatus::UnderReview], true)
                        && auth()->user()?->can('reject', Activity::class) === true)
                    ->action(fn (Activity $record, array $data) => self::runTransition(
                        $record,
                        'activities.rejected',
                        fn (ActivityPublicationService $service, Activity $activity) => $service->reject($activity, auth()->id(), $data['reason']),
                        $data['reason'],
                    )),
                Action::make('suspend')
                    ->color('warning')
                    ->icon('heroicon-o-pause-circle')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                    ])
                    ->visible(fn (Activity $record): bool => $record->status === ActivityStatus::Published
                        && auth()->user()?->can('suspend', Activity::class) === true)
                    ->action(fn (Activity $record, array $data) => self::runTransition(
                        $record,
                        'activities.suspended',
                        fn (ActivityPublicationService $service, Activity $activity) => $service->suspend($activity, auth()->id(), $data['reason']),
                        $data['reason'],
                    )),
            ])
            ->emptyStateHeading('No activities need administration');
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
                    TextEntry::make('category.name')->label('Category')->placeholder('—'),
                    TextEntry::make('duration_minutes')->label('Duration (minutes)'),
                    TextEntry::make('base_price')->label('Base price')->money(fn (Activity $record) => $record->currency),
                    TextEntry::make('description')->columnSpanFull(),
                    TextEntry::make('safety_instructions')->columnSpanFull()->placeholder('No safety instructions supplied'),
                ]),
            Section::make('Schedules and sessions')
                ->schema([
                    RepeatableEntry::make('schedules')
                        ->schema([
                            TextEntry::make('day_of_week')->label('Day'),
                            TextEntry::make('start_time'),
                            TextEntry::make('end_time'),
                            TextEntry::make('timezone'),
                            TextEntry::make('capacity'),
                        ])->columns(5),
                ]),
            Section::make('Decision history')
                ->schema([
                    RepeatableEntry::make('verificationHistory')
                        ->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('status'),
                            TextEntry::make('reviewer.name')->label('Reviewer')->placeholder('—'),
                            TextEntry::make('notes')->columnSpanFull()->placeholder('—'),
                        ])->columns(3),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['provider', 'destination', 'category', 'schedules', 'verificationHistory.reviewer']);
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
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    private static function runTransition(
        Activity $record,
        string $action,
        callable $transition,
        ?string $reason = null,
    ): void {
        DB::transaction(function () use ($record, $action, $transition, $reason): void {
            $before = ['status' => $record->status?->value];
            $updated = $transition(app(ActivityPublicationService::class), $record->fresh());

            app(AuditRecorder::class)->record(
                $action,
                $updated,
                auth()->user(),
                before: $before,
                after: ['status' => $updated->status?->value],
                reason: $reason,
            );
        });

        Notification::make()->success()->title('Activity moderation decision recorded.')->send();
    }
}
