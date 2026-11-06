<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\Package;
use App\Support\Currencies;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * How the marketplace is trading: money in, bookings, cancellations, growth
 * and live supply over the last 30 days against the 30 before.
 *
 * Money is never added across currencies. The headline is the currency with
 * the most value this period; the others are listed under it.
 *
 * Package component bookings (package_booking_id set) are left out of every
 * count: the package booking itself already carries the guest's spend.
 */
class BusinessPulse extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Business pulse';

    protected ?string $description = 'Last 30 days, compared with the 30 days before.';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('reports.view') || $user?->can('bookings.view');
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $now = now();
        $current = [$now->copy()->subDays(30), $now];
        $previous = [$now->copy()->subDays(60), $now->copy()->subDays(30)];

        $stats = [$this->grossValue($current, $previous)];

        $bookings = $this->bookings($current)->count();
        $before = $this->bookings($previous)->count();
        $stats[] = Stat::make('Bookings', number_format($bookings))
            ->description($this->change($bookings, $before).' vs previous 30 days')
            ->descriptionIcon($bookings >= $before ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($bookings >= $before ? 'success' : 'danger')
            ->chart($this->dailyCounts($current));

        $cancelled = $this->bookings($current)->where('status', 'cancelled')->count();
        $rate = $bookings > 0 ? round($cancelled / $bookings * 100, 1) : 0;
        $stats[] = Stat::make('Cancellation rate', $rate.'%')
            ->description("{$cancelled} of {$bookings} bookings cancelled")
            ->descriptionIcon('heroicon-m-x-circle')
            ->color($rate > 15 ? 'danger' : ($rate > 8 ? 'warning' : 'success'));

        $joined = User::query()->whereBetween('created_at', $current)->count();
        $joinedBefore = User::query()->whereBetween('created_at', $previous)->count();
        $stats[] = Stat::make('New accounts', number_format($joined))
            ->description($this->change($joined, $joinedBefore).' · '.number_format(User::query()->count()).' in total')
            ->descriptionIcon('heroicon-m-user-plus')
            ->color('primary');

        $stays = Accommodation::query()->where('status', AccommodationStatus::Published)->count();
        $experiences = Activity::query()->where('status', ActivityStatus::Published)->count();
        $packages = Package::query()->where('status', 'published')->count();
        $stats[] = Stat::make('Live listings', number_format($stays + $experiences + $packages))
            ->description("{$stays} stays · {$experiences} experiences · {$packages} journeys")
            ->descriptionIcon('heroicon-m-globe-europe-africa')
            ->color('primary');

        $upcoming = Booking::query()
            ->whereNull('package_booking_id')
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereBetween('check_in_date', [today(), today()->addDays(7)])
            ->count();
        $stats[] = Stat::make('Arrivals in the next 7 days', number_format($upcoming))
            ->description('Confirmed and pending bookings starting this week')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('primary');

        return $stats;
    }

    /** @param  array{0: \DateTimeInterface, 1: \DateTimeInterface}  $range */
    private function bookings(array $range): Builder
    {
        return Booking::query()
            ->whereNull('package_booking_id')
            ->whereBetween('created_at', $range);
    }

    private function grossValue(array $current, array $previous): Stat
    {
        $sum = fn (array $range): Collection => $this->bookings($range)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('currency, sum(total_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($total) => (float) $total)
            ->sortDesc();

        $now = $sum($current);
        $before = $sum($previous);

        if ($now->isEmpty()) {
            return Stat::make('Gross booking value', '—')->description('No bookings in the last 30 days');
        }

        $currency = $now->keys()->first();
        $value = $now->first();
        $prior = (float) ($before[$currency] ?? 0);
        $others = $now->except($currency)
            ->map(fn ($total, $code) => Currencies::format($total, $code))
            ->implode(' · ');

        return Stat::make('Gross booking value', Currencies::format($value, $currency))
            ->description($this->change($value, $prior).($others ? ' · also '.$others : ' vs previous 30 days'))
            ->descriptionIcon($value >= $prior ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($value >= $prior ? 'success' : 'danger');
    }

    /** @return array<int, int> */
    private function dailyCounts(array $range): array
    {
        $counts = $this->bookings($range)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(29, 0))
            ->map(fn ($days) => (int) ($counts[now()->subDays($days)->toDateString()] ?? 0))
            ->all();
    }

    private function change(float $now, float $before): string
    {
        if ($before == 0) {
            return $now > 0 ? 'New' : 'No change';
        }

        $percent = round(($now - $before) / $before * 100);

        return ($percent >= 0 ? '+' : '').$percent.'%';
    }
}
