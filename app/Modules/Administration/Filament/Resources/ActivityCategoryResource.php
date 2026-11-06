<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Administration\Filament\Resources\ActivityCategoryResource\Pages;
use App\Modules\Administration\Filament\Support\AdminAudit;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Experience categories, as the experience wizard and the discovery filters
 * list them: in sort_order, active ones only.
 */
class ActivityCategoryResource extends Resource
{
    protected static ?string $model = ActivityCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Experience categories';

    protected static ?string $modelLabel = 'experience category';

    protected static ?int $navigationSort = 30;

    protected static string|\UnitEnum|null $navigationGroup = 'Content & catalog';

    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        // Reordering by drag writes sort_order straight to the rows without an
        // audit entry, so ordering is edited through the form instead.
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
                    Select::make('parent_id')
                        ->label('Parent category')
                        ->relationship('parent', 'name', fn (Builder $query, ?ActivityCategory $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->id)))
                        ->searchable()
                        ->preload(),
                    TextInput::make('sort_order')->numeric()->integer()->minValue(0)->maxValue(32767)->default(0)->required()
                        ->helperText('Lower numbers are listed first.'),
                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn (ActivityCategory $record): string => $record->slug),
                TextColumn::make('parent.name')->label('Parent')->placeholder('—'),
                TextColumn::make('activities_count')->label('Experiences')->sortable()->alignEnd(),
                IconColumn::make('is_active')->label('Active')->boolean()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (ActivityCategory $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (ActivityCategory $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->can('catalog.manage') === true)
                    ->action(function (ActivityCategory $record): void {
                        $before = ['is_active' => $record->is_active];
                        $record->update(['is_active' => ! $record->is_active]);
                        AdminAudit::record('activity_categories.'.($record->is_active ? 'activated' : 'deactivated'), $record, $before, ['is_active' => $record->is_active]);
                        Notification::make()->success()->title('Category '.($record->is_active ? 'activated.' : 'deactivated.'))->send();
                    }),
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->can('catalog.manage') === true)
                    ->disabled(fn (ActivityCategory $record): bool => $record->activities_count > 0)
                    ->tooltip(fn (ActivityCategory $record): ?string => $record->activities_count > 0 ? 'Experiences use this category. Deactivate it instead.' : null)
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->required()->minLength(5)->maxLength(1000)])
                    ->action(function (ActivityCategory $record, array $data): void {
                        if ($record->activities()->exists()) {
                            Notification::make()->danger()->title('Experiences still use this category.')->send();

                            return;
                        }
                        $before = $record->only(['id', 'name', 'slug', 'parent_id', 'sort_order', 'is_active']);
                        $record->delete();
                        AdminAudit::record('activity_categories.deleted', $record, $before, ['deleted' => true], $data['reason']);
                        Notification::make()->success()->title("Deleted “{$before['name']}”.")->send();
                    }),
            ])
            ->emptyStateHeading('No experience categories yet');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('parent')->withCount('activities');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityCategories::route('/'),
            'create' => Pages\CreateActivityCategory::route('/create'),
            'edit' => Pages\EditActivityCategory::route('/{record}/edit'),
        ];
    }
}
