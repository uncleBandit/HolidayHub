<?php

namespace App\Modules\Administration\Filament\Pages;

use App\Modules\Administration\Filament\Widgets\BookingsTrendChart;
use App\Modules\Administration\Filament\Widgets\BusinessPulse;
use App\Modules\Administration\Filament\Widgets\LatestBookings;
use App\Modules\Administration\Filament\Widgets\PlatformOverview;
use App\Modules\Administration\Filament\Widgets\RevenueChart;
use App\Modules\Administration\Filament\Widgets\ReviewQueue;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The panel's landing page.
 *
 * Filament has no home page until one is registered, so without this /admin
 * 302s to the first navigation item instead of showing anything. Registering it
 * explicitly also gives the widgets a page to live on.
 *
 * Reading order: how the business is trading, where money and bookings are
 * heading, what is waiting for a decision, then the latest bookings. Each
 * widget hides itself from roles without the matching permission.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Platform overview';

    public function getSubheading(): ?string
    {
        return 'Good '.match (true) {
            now()->hour < 12 => 'morning',
            now()->hour < 17 => 'afternoon',
            default => 'evening',
        }.', '.str(auth()->user()?->name)->before(' ').'. Here is how HolidayHub is doing.';
    }

    /**
     * @return array<int, class-string>
     */
    public function getWidgets(): array
    {
        return [
            BusinessPulse::class,
            RevenueChart::class,
            BookingsTrendChart::class,
            ReviewQueue::class,
            PlatformOverview::class,
            LatestBookings::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 2];
    }
}
