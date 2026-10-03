<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\MediaModerationResource\Pages;
use App\Modules\Media\Application\Services\MediaModerationService;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Models\MediaPost;
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

class MediaModerationResource extends Resource
{
    protected static ?string $model = MediaPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-film';

    protected static ?string $navigationLabel = 'Media moderation';

    protected static ?int $navigationSort = 40;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('media.view') === true;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('media.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->searchable()->placeholder('Untitled'),
                TextColumn::make('provider.company_name')->label('Provider')->searchable()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('targetable.name')->label('Linked listing')->placeholder('Provider profile only'),
                TextColumn::make('created_at')->label('Uploaded')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(MediaPostStatus::cases())
                    ->mapWithKeys(fn (MediaPostStatus $status): array => [
                        $status->value => str($status->value)->headline()->toString(),
                    ])->all()),
            ])
            ->defaultSort('created_at', 'asc')
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve and publish')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MediaPost $record): bool => $record->status === MediaPostStatus::PendingReview
                        && auth()->user()?->can('media.moderate') === true)
                    ->action(function (MediaPost $record): void {
                        app(MediaModerationService::class)->approve($record, auth()->user());
                        Notification::make()->success()->title('Media published.')->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                    ])
                    ->visible(fn (MediaPost $record): bool => $record->status === MediaPostStatus::PendingReview
                        && auth()->user()?->can('media.moderate') === true)
                    ->action(function (MediaPost $record, array $data): void {
                        app(MediaModerationService::class)->reject($record, auth()->user(), $data['reason']);
                        Notification::make()->success()->title('Media rejected.')->send();
                    }),
            ])
            ->emptyStateHeading('No media submissions');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Submission')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->placeholder('Untitled'),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('provider.company_name')->label('Provider'),
                    TextEntry::make('caption')->columnSpanFull()->placeholder('No caption'),
                    TextEntry::make('targetable.name')->label('Linked listing')->placeholder('Provider profile only'),
                    TextEntry::make('moderation_notes')->label('Moderation note')->columnSpanFull()->placeholder('—'),
                ]),
            Section::make('Assets')
                ->schema([
                    RepeatableEntry::make('assets')
                        ->schema([
                            TextEntry::make('type')->badge(),
                            TextEntry::make('mime_type'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('size_bytes')->label('Bytes')->numeric(),
                            TextEntry::make('url')->label('Temporary preview URL')->copyable()->columnSpanFull(),
                        ])->columns(4),
                ]),
            Section::make('Review')
                ->columns(2)
                ->schema([
                    TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('Not reviewed'),
                    TextEntry::make('published_at')->dateTime()->placeholder('Not published'),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['provider', 'targetable', 'assets', 'reviewer']);
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
            'index' => Pages\ListMediaPosts::route('/'),
            'view' => Pages\ViewMediaPost::route('/{record}'),
        ];
    }
}
