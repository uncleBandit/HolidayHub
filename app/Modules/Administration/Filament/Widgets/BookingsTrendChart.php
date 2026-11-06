<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Booking\Domain\Models\Booking;
use Filament\Widgets\ChartWidget;

/**
 * Bookings per week for the last twelve weeks, one line per kind of listing,
 * so a slide in one part of the marketplace is visible at a glance.
 */
class BookingsTrendChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Bookings by week';

    protected ?string $description = 'Last 12 weeks, by kind of listing.';

    protected ?string $maxHeight = '280px';

    /** Line colours follow the site palette: forest, clay, gold, sage, ink. */
    private const SERIES = [
        'hotel' => ['Hotels', '#16392F'],
        'villa' => ['Villas', '#D85F3C'],
        'bed_and_breakfast' => ['B&Bs', '#C99A4A'],
        'activity' => ['Experiences', '#6E9C83'],
        'package' => ['Journeys', '#3B4A6B'],
    ];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('reports.view') || $user?->can('bookings.view');
    }

    protected function getData(): array
    {
        $start = now()->startOfWeek()->subWeeks(11);

        $rows = Booking::query()
            ->whereNull('package_booking_id')
            ->where('created_at', '>=', $start)
            ->selectRaw("bookable_type, date_trunc('week', created_at)::date as week, count(*) as total")
            ->groupBy('bookable_type', 'week')
            ->get();

        $weeks = collect(range(0, 11))->map(fn ($i) => $start->copy()->addWeeks($i));

        $datasets = collect(self::SERIES)
            ->filter(fn ($series, $type) => $rows->contains('bookable_type', $type))
            ->map(function ($series, $type) use ($rows, $weeks) {
                [$label, $colour] = $series;
                $byWeek = $rows->where('bookable_type', $type)->pluck('total', 'week');

                return [
                    'label' => $label,
                    'data' => $weeks->map(fn ($week) => (int) ($byWeek[$week->toDateString()] ?? 0))->all(),
                    'borderColor' => $colour,
                    'backgroundColor' => $colour.'22',
                    'tension' => 0.35,
                    'fill' => false,
                    'pointRadius' => 2,
                ];
            })
            ->values()
            ->all();

        return [
            'datasets' => $datasets,
            'labels' => $weeks->map(fn ($week) => $week->format('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
