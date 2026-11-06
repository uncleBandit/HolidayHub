<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\PackageModerationResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Packages\Application\Services\PackagePricing;
use App\Modules\Packages\Application\Services\PackagePublicationService;
use App\Modules\Packages\Domain\Models\Package;
use App\Support\Currencies;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Moderation for agents' packages ("journeys"): multi-day trips built from
 * live stays and experiences plus the agent's own extras.
 *
 * Every transition goes through PackagePublicationService, which re-checks
 * the package is complete and every listing in it is still live, and writes
 * the decision to the package's history and the platform audit log.
 */
class PackageModerationResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $slug = 'journeys';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'journey';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Journeys';

    protected static ?int $navigationSort = 30;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    private const STATUS_COLOURS = [
        'draft' => 'gray',
        'pending_review' => 'warning',
        'published' => 'success',
        'rejected' => 'danger',
        'suspended' => 'danger',
    ];

    /**
     * PackagePolicy predates the operational admin roles (it checks the
     * legacy `admin` role), so the panel authorises on permissions instead.
     */
    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        return in_array($action, ['viewAny', 'view'], true) && auth()->user()?->can('packages.view') === true
            ? Response::allow()
            : Response::deny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('packages.view') === true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('packages.view') === true;
    }

    public static function getNavigationBadge(): ?string
    {
        $waiting = Package::query()->where('status', 'pending_review')->count();

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

    public static function publicUrl(Package $record): ?string
    {
        return filled($record->slug) ? route('packages-show', $record->slug) : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold')
                    ->description(fn (Package $record) => $record->short_description ? str($record->short_description)->limit(70)->toString() : null),
                TextColumn::make('agent.agency_name')->label('Agent')->searchable()->placeholder('—')
                    ->description(fn (Package $record) => $record->agent?->is_verified ? 'Verified' : 'Not verified'),
                TextColumn::make('destination.name')->label('Destination')->placeholder('—'),
                TextColumn::make('duration_days')->label('Days')->alignCenter(),
                TextColumn::make('components_count')->label('Parts')->counts('components')->alignCenter(),
                TextColumn::make('markup')->label('Margin')
                    ->state(fn (Package $record) => $record->markup_type === 'fixed'
                        ? Currencies::format($record->markup_value, $record->getCurrency())
                        : rtrim(rtrim(number_format((float) $record->markup_value, 2), '0'), '.').'%'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (?string $state) => str($state)->replace('_', ' ')->headline()->toString())
                    ->color(fn (?string $state) => self::STATUS_COLOURS[$state] ?? 'gray'),
                IconColumn::make('is_featured')->label('Featured')->boolean()->alignCenter(),
                TextColumn::make('submitted_at')->label('Submitted')->since()->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(Package::STATUSES)->mapWithKeys(fn ($s) => [$s => str($s)->replace('_', ' ')->headline()->toString()])->all()),
                TernaryFilter::make('is_featured')->label('Featured'),
                SelectFilter::make('agent')->relationship('agent', 'agency_name')->searchable()->preload(),
            ])
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw("case when status = 'pending_review' then 0 else 1 end")
                ->orderBy('submitted_at'))
            ->recordActions([
                ViewAction::make(),
                ...self::moderationActions(),
            ])
            ->emptyStateHeading('No journeys yet')
            ->emptyStateDescription('Journeys appear here once travel agents start building them.');
    }

    /**
     * Shared by the table and the view page.
     *
     * @return array<int, Action>
     */
    public static function moderationActions(): array
    {
        $can = fn (string $permission) => auth()->user()?->can($permission) === true;
        $reason = fn (string $label) => [
            Textarea::make('reason')->label($label)->required()->minLength(10)->maxLength(1000)->rows(4),
        ];

        return [
            Action::make('approve')
                ->label('Approve and publish')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('The journey goes live and guests can book it. Every stay and experience in it is re-checked first.')
                ->visible(fn (Package $record) => $record->status === 'pending_review' && $can('packages.approve'))
                ->action(fn (Package $record) => self::run($record, 'Journey published.',
                    fn (PackagePublicationService $s, Package $p) => $s->approve($p, auth()->user()))),
            Action::make('reject')
                ->label('Request changes')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->schema($reason('What should the agent change?'))
                ->visible(fn (Package $record) => in_array($record->status, ['pending_review', 'published'], true) && $can('packages.reject'))
                ->action(fn (Package $record, array $data) => self::run($record, 'Sent back to the agent.',
                    fn (PackagePublicationService $s, Package $p) => $s->reject($p, $data['reason'], auth()->user()))),
            Action::make('suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->schema($reason('Why is it being taken down?'))
                ->modalDescription('Guests can no longer find or book it. Existing bookings are kept.')
                ->visible(fn (Package $record) => $record->status === 'published' && $can('packages.suspend'))
                ->action(fn (Package $record, array $data) => self::run($record, 'Journey suspended.',
                    fn (PackagePublicationService $s, Package $p) => $s->suspend($p, $data['reason'], auth()->user()))),
            Action::make('reinstate')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Package $record) => $record->status === 'suspended' && $can('packages.suspend'))
                ->action(fn (Package $record) => self::run($record, 'Journey reinstated.',
                    fn (PackagePublicationService $s, Package $p) => $s->reinstate($p, auth()->user()))),
            Action::make('feature')
                ->label(fn (Package $record) => $record->is_featured ? 'Unfeature' : 'Feature')
                ->icon(fn (Package $record) => $record->is_featured ? 'heroicon-o-star' : 'heroicon-s-star')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(fn (Package $record) => $record->is_featured
                    ? 'It stops being promoted in featured placements.'
                    : 'It is promoted in featured placements across the site.')
                ->visible(fn (Package $record) => $record->status === 'published' && $can('packages.approve'))
                ->action(function (Package $record) {
                    $before = (bool) $record->is_featured;
                    $record->forceFill(['is_featured' => ! $before])->saveQuietly();
                    app(AuditRecorder::class)->record(
                        $before ? 'package.unfeatured' : 'package.featured',
                        $record,
                        auth()->user(),
                        before: ['is_featured' => $before],
                        after: ['is_featured' => ! $before],
                    );
                    Notification::make()->success()->title($before ? 'No longer featured.' : 'Now featured.')->send();
                }),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('The journey')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->columnSpan(2),
                    TextEntry::make('status')->badge()
                        ->formatStateUsing(fn (?string $state) => str($state)->replace('_', ' ')->headline()->toString())
                        ->color(fn (?string $state) => self::STATUS_COLOURS[$state] ?? 'gray'),
                    TextEntry::make('agent.agency_name')->label('Agent')->placeholder('—')
                        ->url(fn (Package $record) => $record->agent && AgentResource::canViewAny()
                            ? AgentResource::getUrl('view', ['record' => $record->agent])
                            : null),
                    TextEntry::make('agent_standing')->label('Agent standing')
                        ->state(fn (Package $record) => ! $record->agent ? 'No agent' : (($record->agent->is_verified ? 'Verified' : 'Not verified').($record->agent->active ? '' : ' · suspended')))
                        ->badge()
                        ->color(fn (Package $record) => $record->agent?->is_verified && $record->agent?->active ? 'success' : 'warning'),
                    TextEntry::make('destination.name')->label('Destination')->placeholder('—'),
                    TextEntry::make('duration')->label('Length')
                        ->state(fn (Package $record) => $record->duration_days.' days · '.$record->duration_nights.' nights'),
                    TextEntry::make('guests')->label('Group size')
                        ->state(fn (Package $record) => ($record->min_guests ?: 1).'–'.($record->max_guests ?: '?').' guests'),
                    TextEntry::make('pricing')->label('Agent margin')
                        ->state(fn (Package $record) => ($record->markup_type === 'fixed'
                            ? Currencies::format($record->markup_value, $record->getCurrency()).' per booking'
                            : (float) $record->markup_value.'% on listing prices').' · priced in '.$record->getCurrency()),
                    TextEntry::make('short_description')->label('Hook')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('full_description')->label('Story')->columnSpanFull()->placeholder('—')->prose(),
                    TextEntry::make('rejection_reason')->label(fn (Package $record) => $record->status === 'suspended' ? 'Suspension reason' : 'Changes requested')
                        ->columnSpanFull()->color('danger')
                        ->visible(fn (Package $record) => filled($record->rejection_reason)),
                ]),
            Section::make('Ready to publish?')
                ->description('The same checklist the agent sees. Approving re-checks it.')
                ->schema([
                    ViewEntry::make('readiness')->hiddenLabel()->view('administration::filament.packages.readiness'),
                ]),
            Section::make('Day by day')
                ->description('Each stay and experience links to its listing; prices are a live sample quote.')
                ->schema([
                    ViewEntry::make('itinerary')->hiddenLabel()->view('administration::filament.packages.itinerary'),
                ]),
            Section::make('Photos, film and the journey reel')
                ->collapsible()
                ->schema([
                    ViewEntry::make('media_preview')->hiddenLabel()->view('administration::filament.moderation.media-preview'),
                ]),
            Section::make('Decision history')
                ->collapsible()
                ->collapsed()
                ->schema([
                    ViewEntry::make('history')->hiddenLabel()->view('administration::filament.packages.history'),
                ]),
        ]);
    }

    /**
     * The checklist and a live quote for the view page, worked out once per
     * render. Failures are shown rather than thrown, so a broken listing in a
     * journey never makes the moderation page unusable.
     *
     * @return array{requirements: array, quote: ?array, error: ?string}
     */
    public static function review(Package $record): array
    {
        try {
            $requirements = app(PackagePublicationService::class)->requirements($record);
        } catch (\Throwable $e) {
            report($e);
            $requirements = [];
        }

        try {
            $guests = max(2, (int) $record->min_guests);
            $quote = app(PackagePricing::class)->sampleQuote($record, $guests);

            return ['requirements' => $requirements, 'quote' => $quote, 'error' => null];
        } catch (\Throwable $e) {
            report($e);

            return ['requirements' => $requirements, 'quote' => null, 'error' => $e->getMessage()];
        }
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['agent', 'destination']);
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
            'index' => Pages\ListPackages::route('/'),
            'view' => Pages\ViewPackage::route('/{record}'),
        ];
    }

    /**
     * Run a publication transition and report the outcome. The service records
     * the decision (package history and audit log) itself.
     */
    private static function run(Package $record, string $done, callable $transition): void
    {
        try {
            $transition(app(PackagePublicationService::class), $record->fresh());
        } catch (ValidationException $e) {
            Notification::make()->danger()->title('Not yet')->body(collect($e->errors())->flatten()->implode(' '))->send();

            return;
        } catch (LogicException $e) {
            Notification::make()->danger()->title('That change is not possible')->body($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($done)->send();
    }
}
