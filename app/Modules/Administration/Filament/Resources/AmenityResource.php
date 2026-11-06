<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\AmenityResource\Pages;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Catalog\Domain\Models\Amenity;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The amenity catalogue the listing wizards offer hosts.
 *
 * The wizards pick amenities by type: hotel and B&B property amenities are
 * `general`, rooms are `room`, villas take `villa` plus `general`, and
 * packages `package`. Deactivating an amenity hides it from the wizards
 * without touching listings that already have it.
 */
class AmenityResource extends Resource
{
    /** The types allowed by the amenities_type_check constraint. */
    public const TYPES = [
        'general' => 'General (hotel and B&B property, villa)',
        'room' => 'Room',
        'villa' => 'Villa',
        'hotel' => 'Hotel',
        'package' => 'Package',
    ];

    protected static ?string $model = Amenity::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Amenities';

    protected static ?int $navigationSort = 20;

    protected static string|\UnitEnum|null $navigationGroup = 'Content & catalog';

    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (in_array($action, ['delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny', 'replicate', 'reorder'], true)) {
            return Response::deny();
        }

        return auth()->user()?->can('catalog.manage') === true ? Response::allow() : Response::deny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true),
                    Select::make('type')->options(self::TYPES)->required()->default('general'),
                    TextInput::make('icon')
                        ->maxLength(255)
                        ->helperText('Storage path of the icon, e.g. amenities/icons/wifi.svg.'),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                    Toggle::make('active')->default(true)->helperText('Inactive amenities are hidden from the listing wizards.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold')
                    ->description(fn (Amenity $record): ?string => $record->description ? Str::limit($record->description, 70) : null),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('icon')->placeholder('—')->toggleable(),
                TextColumn::make('usage_count')->label('Used by')->suffix(' listings')->sortable()->alignEnd(),
                IconColumn::make('active')->boolean()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(self::TYPES),
                TernaryFilter::make('active'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Amenity $record): string => $record->active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Amenity $record): string => $record->active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->can('catalog.manage') === true)
                    ->action(function (Amenity $record): void {
                        $before = ['active' => $record->active];
                        $record->update(['active' => ! $record->active]);
                        app(AuditRecorder::class)->record('amenities.'.($record->active ? 'activated' : 'deactivated'), $record, auth()->user(),
                            before: $before, after: ['active' => $record->active]);
                        Notification::make()->success()->title('Amenity '.($record->active ? 'activated.' : 'deactivated.'))->send();
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->can('catalog.manage') === true)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Amenity $record): string => $record->usage_count > 0
                        ? "“{$record->name}” is on {$record->usage_count} listings. Deleting it removes it from all of them. Deactivating it instead hides it from the wizards and keeps existing listings unchanged."
                        : "Delete “{$record->name}”? No listing uses it.")
                    ->schema([Textarea::make('reason')->required()->minLength(5)->maxLength(1000)])
                    ->action(function (Amenity $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $usage = DB::table('amenables')->where('amenity_id', $record->id)
                                ->select('amenable_type', DB::raw('count(*) as n'))->groupBy('amenable_type')->pluck('n', 'amenable_type')->all();
                            $before = $record->only(['id', 'name', 'slug', 'type', 'icon', 'active']);

                            DB::table('amenables')->where('amenity_id', $record->id)->delete();
                            $record->delete();

                            app(AuditRecorder::class)->record('amenities.deleted', $record, auth()->user(),
                                before: $before + ['attached_to' => $usage], after: ['deleted' => true], reason: $data['reason']);
                        });
                        Notification::make()->success()->title("Deleted “{$record->name}”.")->send();
                    }),
            ])
            ->emptyStateHeading('No amenities yet');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->addSelect([
            'usage_count' => DB::table('amenables')->selectRaw('count(*)')->whereColumn('amenables.amenity_id', 'amenities.id'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmenities::route('/'),
            'create' => Pages\CreateAmenity::route('/create'),
            'edit' => Pages\EditAmenity::route('/{record}/edit'),
        ];
    }
}
