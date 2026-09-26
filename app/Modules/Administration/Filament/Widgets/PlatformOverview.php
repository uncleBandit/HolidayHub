<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Administration\Domain\Enums\TenantStatus;
use App\Modules\Administration\Domain\Models\Tenant;
use App\Modules\Administration\Filament\Resources\TenantResource;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\User;
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
        $pendingTenants = Tenant::query()
            ->awaitingReview()
            ->count();

        $bookingsLast30Days = Booking::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $unverifiedAccounts = User::query()
            ->whereNull('email_verified_at')
            ->count();

        return [
            Stat::make('Tenants awaiting review', $pendingTenants)
                ->description($pendingTenants > 0
                    ? 'Oldest applications are listed first in the review queue.'
                    : 'Nothing waiting. The queue is clear.')
                ->descriptionIcon($pendingTenants > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingTenants > 0 ? 'warning' : 'success')
                ->url(TenantResource::getUrl('index')),

            Stat::make('Approved tenants', Tenant::query()->approved()->count())
                ->description('Cleared to list services on the platform.')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->url(TenantResource::getUrl('index')),

            Stat::make('Suspended tenants', Tenant::query()
                ->where('status', TenantStatus::Suspended->value)
                ->count())
                ->description('Verified previously, then withdrawn.')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('gray'),

            Stat::make('Platform accounts', User::query()->count())
                ->description("{$unverifiedAccounts} with unverified email address.")
                ->descriptionIcon('heroicon-m-users')
                ->color($unverifiedAccounts > 0 ? 'warning' : 'success'),

            Stat::make('Bookings (30 days)', $bookingsLast30Days)
                ->description('All time: '.Booking::query()->count())
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getColumns(): int
    {
        return 3;
    }
}
