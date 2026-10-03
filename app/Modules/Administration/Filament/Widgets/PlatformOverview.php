<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Filament\Resources\AccommodationModerationResource;
use App\Modules\Administration\Filament\Resources\ActivityModerationResource;
use App\Modules\Administration\Filament\Resources\MediaModerationResource;
use App\Modules\Administration\Filament\Resources\TenantResource;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Models\MediaPost;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Platform health at a glance, for the admin panel dashboard.
 *
 * Counts are cheap queries on indexed columns rather than anything aggregate
 * over bookings or payouts, so the panel stays responsive as the marketplace
 * grows. The cards deliberately surface *outflows needing attention* (pending
 * verifications, unverified accounts) alongside the headline totals, because
 * those are the numbers an admin acts on.
 */
class PlatformOverview extends StatsOverviewWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user?->can('tenants.view')) {
            $pending = Tenant::query()->awaitingReview()->count();
            $stats[] = Stat::make('Tenants awaiting review', $pending)
                ->description($pending > 0 ? 'Oldest applications are listed first.' : 'The review queue is clear.')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(TenantResource::getUrl('index'));
        }

        if ($user?->can('accommodations.view')) {
            $pending = Accommodation::query()->where('status', AccommodationStatus::PendingReview)->count();
            $stats[] = Stat::make('Accommodations awaiting review', $pending)
                ->description('Listings that need an operations decision.')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(AccommodationModerationResource::getUrl('index'));
        }

        if ($user?->can('activities.view')) {
            $pending = Activity::query()->where('status', ActivityStatus::Submitted)->count();
            $stats[] = Stat::make('Activities awaiting review', $pending)
                ->description('Submitted listings not yet picked up by a reviewer.')
                ->descriptionIcon('heroicon-m-ticket')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(ActivityModerationResource::getUrl('index'));
        }

        if ($user?->can('media.view')) {
            $pending = MediaPost::query()->where('status', MediaPostStatus::PendingReview)->count();
            $stats[] = Stat::make('Media awaiting review', $pending)
                ->description('Provider videos and reels awaiting moderation.')
                ->descriptionIcon('heroicon-m-film')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(MediaModerationResource::getUrl('index'));
        }

        if ($user?->can('users.view')) {
            $unverified = User::query()->whereNull('email_verified_at')->count();
            $stats[] = Stat::make('Platform accounts', User::query()->count())
                ->description("{$unverified} with unverified email addresses.")
                ->descriptionIcon('heroicon-m-users')
                ->color($unverified > 0 ? 'warning' : 'success');
        }

        if ($user?->can('bookings.view')) {
            $last30Days = Booking::query()->where('created_at', '>=', now()->subDays(30))->count();
            $today = Booking::query()->whereDate('created_at', today())->count();
            $stats[] = Stat::make('Bookings (30 days)', $last30Days)
                ->description("Today: {$today}")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->url(\App\Modules\Administration\Filament\Resources\BookingOperationsResource::getUrl('index'));
        }

        return $stats;
    }

    /**
     * @return array<int, string>
     */
    protected function getColumns(): int
    {
        return 3;
    }
}
