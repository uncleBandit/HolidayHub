<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\DestinationResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Destinations\Application\Services\DestinationMerger;
use App\Modules\Destinations\Application\Services\DestinationResolver;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Providers\Domain\Models\Provider;
use App\Support\Countries;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The destination catalogue.
 *
 * Hosts create destinations from the stay and experience wizards through
 * DestinationResolver, so the catalogue grows on its own; this is where an
 * admin curates it, spots the duplicates the resolver's own matching rules
 * would flag, and folds them together without orphaning a single listing.
 */
class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Destinations';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Content & catalog';

    /** @var array<int, list<int>>|null id => ids it likely duplicates, per request */
    private static ?array $duplicateMap = null;

    /** @var array<int, string>|null */
    private static ?array $hostNames = null;

    /** @var array<int, string>|null */
    private static ?array $names = null;

    /**
     * Admin permissions decide access, not the model policy: Destination's
     * Gate policy is currently registered as DestinationReviewPolicy (see
     * DestinationsServiceProvider), whose type hints reject a Destination.
     */
    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        $permission = in_array($action, ['viewAny', 'view'], true) ? 'destinations.view' : 'destinations.manage';

        return in_array($action, ['delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny', 'replicate', 'reorder'], true)
            || auth()->user()?->can($permission) !== true
                ? Response::deny()
                : Response::allow();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('destinations.view') === true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('destinations.view') === true;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('destinations.manage') === true;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('destinations.manage') === true;
    }

    /** Deletion goes through the guarded table action, never a bulk delete. */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Place')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state.' '.$get('country')));
                            }
                        }),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->alphaDash()
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->withoutTrashed()),
                    Select::make('country')
                        ->options(fn (): array => array_combine(array_values(Countries::all()), array_values(Countries::all())))
                        ->searchable()
                        ->required(),
                    TextInput::make('city')->maxLength(255),
                    TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90)->step('any'),
                    TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180)->step('any'),
                ]),
            Section::make('Copy')
                ->columns(2)
                ->schema([
                    TextInput::make('tagline')->maxLength(255),
                    TextInput::make('best_season')->maxLength(255)->placeholder('e.g. June–October'),
                    Textarea::make('short_description')->rows(2)->columnSpanFull(),
                    Textarea::make('description')->rows(6)->columnSpanFull(),
                    TagsInput::make('highlights')->columnSpanFull(),
                    TagsInput::make('tags'),
                    TextInput::make('average_cost')->numeric()->minValue(0)->helperText('Typical daily spend, whole units.'),
                ]),
            Section::make('Hero media')
                ->columns(2)
                ->schema([
                    View::make('administration::filament.destination-hero')->columnSpanFull(),
                    TextInput::make('image_url')
                        ->label('Hero image or film')
                        ->maxLength(255)
                        ->helperText('A URL or a storage path. Uploading below replaces it.'),
                    TextInput::make('thumbnail')->maxLength(255)->helperText('Card image URL or storage path.'),
                    FileUpload::make('hero_upload')
                        ->label('Upload a new hero')
                        ->disk('public')
                        ->directory('destinations')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'video/mp4', 'video/webm', 'video/quicktime'])
                        ->maxSize(204800)
                        ->columnSpanFull(),
                    Repeater::make('gallery')
                        ->simple(TextInput::make('url')->required()->maxLength(2048))
                        ->defaultItems(0)
                        ->columnSpanFull(),
                ]),
            Section::make('Promotion and SEO')
                ->columns(2)
                ->schema([
                    Toggle::make('is_featured')->label('Featured on the home page'),
                    TextInput::make('popularity_score')->numeric()->minValue(0)->default(0)->required(),
                    TextInput::make('meta_title')->maxLength(255),
                    TextInput::make('meta_description')->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(['name', 'city', 'slug'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Destination $record): string => $record->slug),
                TextColumn::make('country')->searchable()->sortable(),
                TextColumn::make('city')->sortable()->placeholder('—'),
                TextColumn::make('accommodations_count')->label('Stays')->sortable()->alignEnd(),
                TextColumn::make('activities_count')->label('Experiences')->sortable()->alignEnd(),
                TextColumn::make('packages_count')->label('Packages')->sortable()->alignEnd(),
                TextColumn::make('origin')
                    ->label('Created by')
                    ->badge()
                    ->state(fn (Destination $record): string => self::origin($record))
                    ->color(fn (string $state): string => str_starts_with($state, 'Host') ? 'info' : 'gray'),
                IconColumn::make('is_featured')->label('Featured')->boolean()->sortable(),
                TextColumn::make('possible_duplicates')
                    ->label('Possible duplicates')
                    ->state(fn (Destination $record): ?string => self::duplicateNames($record))
                    ->color('warning')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('created_at')->label('Added')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('possible_duplicates')
                    ->label('Possible duplicates only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereKey(self::duplicateIds())),
                SelectFilter::make('country')
                    ->options(fn (): array => Destination::query()->distinct()->orderBy('country')->pluck('country', 'country')->all()),
                TernaryFilter::make('is_featured')->label('Featured'),
                SelectFilter::make('origin')
                    ->label('Created by')
                    ->options(['host' => 'A host', 'catalogue' => 'Seeded or admin'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'host' => $query->whereRaw("(meta_data::jsonb ->> 'source') = 'provider'"),
                        'catalogue' => $query->whereRaw("coalesce(meta_data::jsonb ->> 'source', '') <> 'provider'"),
                        default => $query,
                    }),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                Action::make('merge')
                    ->label('Merge into…')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()?->can('destinations.manage') === true)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Destination $record): string => "Merge “{$record->name}” into another destination")
                    ->modalDescription(fn (Destination $record): string => sprintf(
                        'Every listing, package, booking, offer and review that points at “%s” (%d stays, %d experiences, %d packages) moves to the destination you pick, then “%s” is deleted. This cannot be undone from here.',
                        $record->name,
                        $record->accommodations_count,
                        $record->activities_count,
                        $record->packages_count,
                        $record->name,
                    ))
                    ->schema([
                        Select::make('survivor_id')
                            ->label('Keep this destination')
                            ->options(fn (Destination $record): array => self::mergeTargets($record))
                            ->default(fn (Destination $record): ?int => self::duplicateMap()[$record->id][0] ?? null)
                            ->searchable()
                            ->required(),
                        Textarea::make('reason')->required()->minLength(5)->maxLength(1000),
                    ])
                    ->action(fn (Destination $record, array $data) => self::merge($record, (int) $data['survivor_id'], $data['reason'])),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->can('destinations.manage') === true)
                    ->disabled(fn (Destination $record): bool => self::listingCount($record) > 0)
                    ->tooltip(fn (Destination $record): ?string => self::listingCount($record) > 0
                        ? 'Still has listings. Merge it into another destination instead.'
                        : null)
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->required()->minLength(5)->maxLength(1000)])
                    ->action(fn (Destination $record, array $data) => self::delete($record, $data['reason'])),
            ])
            ->emptyStateHeading('No destinations yet');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['accommodations', 'activities', 'packages', 'offers']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDestinations::route('/'),
            'create' => Pages\CreateDestination::route('/create'),
            'edit' => Pages\EditDestination::route('/{record}/edit'),
        ];
    }

    /** Move the hero upload into image_url; the upload field is not a column. */
    public static function applyHeroUpload(array $data): array
    {
        if (filled($data['hero_upload'] ?? null)) {
            $data['image_url'] = is_array($data['hero_upload']) ? reset($data['hero_upload']) : $data['hero_upload'];
        }
        unset($data['hero_upload']);

        return $data;
    }

    private static function listingCount(Destination $record): int
    {
        return (int) $record->accommodations_count + (int) $record->activities_count
            + (int) $record->packages_count + (int) $record->offers_count;
    }

    private static function merge(Destination $duplicate, int $survivorId, string $reason): void
    {
        $survivor = Destination::query()->findOrFail($survivorId);
        $before = [
            'duplicate' => $duplicate->only(['id', 'name', 'slug', 'country', 'city']),
            'survivor' => $survivor->only(['id', 'name', 'slug', 'country', 'city']),
        ];

        $moved = app(DestinationMerger::class)->merge($duplicate, $survivor);

        app(AuditRecorder::class)->record(
            'destinations.merged',
            $duplicate,
            auth()->user(),
            before: $before,
            after: ['merged_into' => $survivor->id, 'deleted' => true],
            reason: $reason,
            meta: ['moved' => $moved],
        );

        self::$duplicateMap = null;
        self::$names = null;

        Notification::make()
            ->success()
            ->title("Merged “{$duplicate->name}” into “{$survivor->name}”.")
            ->body($moved === [] ? 'Nothing pointed at it.' : collect($moved)->map(fn (int $n, string $col) => "{$col}: {$n}")->implode(', '))
            ->send();
    }

    private static function delete(Destination $record, string $reason): void
    {
        $fresh = Destination::query()->withCount(['accommodations', 'activities', 'packages', 'offers'])->findOrFail($record->id);

        if (self::listingCount($fresh) > 0) {
            Notification::make()->danger()->title('This destination still has listings.')
                ->body('Merge it into another destination so its listings move with it.')->send();

            return;
        }

        $before = $fresh->only(['id', 'name', 'slug', 'country', 'city']);
        $fresh->delete();

        app(AuditRecorder::class)->record('destinations.deleted', $fresh, auth()->user(), before: $before, after: ['deleted' => true], reason: $reason);

        Notification::make()->success()->title("Deleted “{$before['name']}”.")->send();
    }

    /** @return array<int, string> suggested duplicates first, then everything else */
    private static function mergeTargets(Destination $record): array
    {
        $suggested = self::duplicateMap()[$record->id] ?? [];
        $label = fn (Destination $d): string => trim("{$d->name} — ".collect([$d->city, $d->country])->filter()->unique()->implode(', '));

        $all = Destination::query()->whereKeyNot($record->id)->orderBy('country')->orderBy('name')->get(['id', 'name', 'city', 'country']);

        return $all->whereIn('id', $suggested)->mapWithKeys(fn (Destination $d) => [$d->id => $label($d).' (likely duplicate)'])->all()
            + $all->whereNotIn('id', $suggested)->mapWithKeys(fn (Destination $d) => [$d->id => $label($d)])->all();
    }

    /** @return array<int, list<int>> */
    private static function duplicateMap(): array
    {
        return self::$duplicateMap ??= app(DestinationResolver::class)->duplicateMap();
    }

    /** @return list<int> every destination on either side of a likely duplicate pair */
    private static function duplicateIds(): array
    {
        $map = self::duplicateMap();

        return array_values(array_unique(array_merge(array_keys($map), ...array_values($map ?: [[]]))));
    }

    private static function duplicateNames(Destination $record): ?string
    {
        $map = self::duplicateMap();
        $ids = array_unique(array_merge(
            $map[$record->id] ?? [],
            array_keys(array_filter($map, fn (array $dupes) => in_array($record->id, $dupes, true))),
        ));

        if ($ids === []) {
            return null;
        }

        self::$names ??= Destination::query()->pluck('name', 'id')->all();

        return collect($ids)->map(fn (int $id) => self::$names[$id] ?? null)->filter()->sort()->implode(', ');
    }

    private static function origin(Destination $record): string
    {
        $meta = (array) ($record->meta_data ?? []);

        if (($meta['source'] ?? null) !== 'provider') {
            return 'Seeded or admin';
        }

        $providerId = $meta['created_by_provider_id'] ?? null;
        self::$hostNames ??= Provider::query()
            ->whereIn('id', Destination::query()->whereRaw("(meta_data::jsonb ->> 'source') = 'provider'")->get(['meta_data'])
                ->pluck('meta_data.created_by_provider_id')->filter()->unique()->all())
            ->pluck('company_name', 'id')
            ->all();

        return $providerId && isset(self::$hostNames[$providerId])
            ? 'Host: '.self::$hostNames[$providerId]
            : 'Host';
    }
}
