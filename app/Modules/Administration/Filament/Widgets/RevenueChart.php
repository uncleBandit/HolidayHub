<?php

namespace App\Modules\Administration\Filament\Widgets;

use App\Modules\Booking\Domain\Models\Booking;
use App\Shared\Support\Currencies;
use Filament\Widgets\ChartWidget;

/**
 * Gross booking value per month for the last six months, in one currency at
 * a time (picked with the filter) because amounts in different currencies
 * cannot be added together. Cancelled value is shown beside it.
 */
class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Gross booking value';

    protected ?string $description = 'Per month, excluding cancellations.';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('reports.view') || $user?->can('payments.view');
    }

    protected function getFilters(): ?array
    {
        return Booking::query()
            ->whereNull('package_booking_id')
            ->selectRaw('currency, count(*) as total')
            ->groupBy('currency')
            ->orderByDesc('total')
            ->pluck('currency')
            ->filter()
            ->mapWithKeys(fn ($code) => [$code => $code.' · '.Currencies::symbol($code)])
            ->all();
    }

    protected function getData(): array
    {
        $currency = $this->filter ?: array_key_first($this->getFilters() ?? []) ?: 'KES';
        $start = now()->startOfMonth()->subMonths(5);

        $rows = Booking::query()
            ->whereNull('package_booking_id')
            ->where('currency', $currency)
            ->where('created_at', '>=', $start)
            ->selectRaw("date_trunc('month', created_at)::date as month, sum(case when status <> 'cancelled' then total_amount else 0 end) as kept, sum(case when status = 'cancelled' then total_amount else 0 end) as lost")
            ->groupBy('month')
            ->get()
            ->keyBy(fn ($row) => (string) $row->month);

        $months = collect(range(0, 5))->map(fn ($i) => $start->copy()->addMonths($i));

        return [
            'datasets' => [
                [
                    'label' => 'Booked ('.$currency.')',
                    'data' => $months->map(fn ($m) => round((float) ($rows[$m->toDateString()]->kept ?? 0)))->all(),
                    'backgroundColor' => '#16392F',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Cancelled ('.$currency.')',
                    'data' => $months->map(fn ($m) => round((float) ($rows[$m->toDateString()]->lost ?? 0)))->all(),
                    'backgroundColor' => '#D85F3C',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $months->map(fn ($m) => $m->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
