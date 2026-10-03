<?php

namespace App\Modules\Audit\Filament\Resources;

use App\Modules\Audit\Domain\Models\AuditLog;
use App\Modules\Audit\Filament\Resources\AuditLogResource\Pages;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit trail';

    protected static ?int $navigationSort = 90;

    protected static string|\UnitEnum|null $navigationGroup = 'Governance';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('admin.name')->label('Actor')->placeholder('System')->searchable(),
                TextColumn::make('action')->badge()->searchable()->sortable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')->label('Record')->sortable(),
                TextColumn::make('reason')->limit(70)->placeholder('—'),
                TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(fn (): array => AuditLog::query()
                        ->whereNotNull('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('No administrative activity recorded');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('admin.name')->label('Actor')->placeholder('System'),
                    TextEntry::make('action')->badge(),
                    TextEntry::make('reason')->placeholder('No reason recorded')->columnSpanFull(),
                    TextEntry::make('subject_type')->label('Subject type'),
                    TextEntry::make('subject_id')->label('Subject ID'),
                ]),
            Section::make('Change')
                ->columns(2)
                ->schema([
                    TextEntry::make('before')->state(fn (AuditLog $record): string => self::json($record->before)),
                    TextEntry::make('after')->state(fn (AuditLog $record): string => self::json($record->after)),
                    TextEntry::make('meta')->state(fn (AuditLog $record): string => self::json($record->meta))->columnSpanFull(),
                ]),
            Section::make('Request')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('ip_address')->placeholder('—'),
                    TextEntry::make('request_id')->placeholder('—'),
                    TextEntry::make('correlation_id')->placeholder('—'),
                    TextEntry::make('user_agent')->columnSpanFull()->placeholder('—'),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('admin');
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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    private static function json(?array $value): string
    {
        return $value === null ? '—' : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
