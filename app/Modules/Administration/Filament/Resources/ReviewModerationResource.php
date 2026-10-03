<?php

namespace App\Modules\Administration\Filament\Resources;

use App\Modules\Administration\Filament\Resources\ReviewModerationResource\Pages;
use App\Modules\Reviews\Application\Services\ReviewModerationService;
use App\Modules\Reviews\Domain\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReviewModerationResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Review moderation';

    protected static ?int $navigationSort = 50;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('reviews.view') === true;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('reviews.view') === true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('guest.user.name')->label('Reviewer')->searchable()->placeholder('—'),
                TextColumn::make('reviewable.name')->label('Listing')->searchable()->placeholder('—'),
                TextColumn::make('type')->badge(),
                TextColumn::make('rating')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->defaultSort('created_at', 'asc')
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => $record->status === 'pending'
                        && auth()->user()?->can('reviews.moderate') === true)
                    ->action(function (Review $record): void {
                        app(ReviewModerationService::class)->approve($record, auth()->user());
                        Notification::make()->success()->title('Review approved.')->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(10)->maxLength(1000),
                    ])
                    ->visible(fn (Review $record): bool => $record->status === 'pending'
                        && auth()->user()?->can('reviews.moderate') === true)
                    ->action(function (Review $record, array $data): void {
                        app(ReviewModerationService::class)->reject($record, auth()->user(), $data['reason']);
                        Notification::make()->success()->title('Review rejected.')->send();
                    }),
            ])
            ->emptyStateHeading('No reviews found');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')->badge(),
                    TextEntry::make('rating'),
                    TextEntry::make('guest.user.name')->label('Reviewer')->placeholder('—'),
                    TextEntry::make('guest.user.email')->label('Reviewer email')->placeholder('—'),
                    TextEntry::make('reviewable.name')->label('Listing')->placeholder('—'),
                    TextEntry::make('booking.confirmation_code')->label('Booking reference')->placeholder('—'),
                    TextEntry::make('title')->columnSpanFull()->placeholder('No title'),
                    TextEntry::make('comment')->columnSpanFull(),
                    TextEntry::make('created_at')->label('Submitted')->dateTime(),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['guest.user', 'reviewable', 'booking']);
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
            'index' => Pages\ListReviews::route('/'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }
}
