<?php

namespace App\Modules\Availability\Application\Services;

use App\Modules\Booking\Domain\Models\Booking;
use App\Shared\Domain\Contracts\AvailabilityAware;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Answers one question: can this be booked, and under what conditions.
 *
 * It does not price anything. Pricing is a separate concern owned by the Pricing
 * module, and this engine used to reach straight into it:
 *
 *     public function __construct(protected ?PricingEngine $pricingEngine = null)
 *     {
 *         $this->pricingEngine ??= new PricingEngine;
 *     }
 *
 *     $price = $this->pricingEngine->calculateDaily($bookable, $date);
 *
 * Two problems with that. It made Availability depend on Pricing, inverting the
 * intended direction — Booking should ask both questions, in whatever order it
 * likes, rather than Availability silently answering Pricing's on its behalf. And
 * the inline `new` hid the dependency from the container, so it could not be
 * overridden or stubbed in a test. The `calculateDaily()` pass-through at the
 * bottom existed only to forward to that hidden instance and went unused.
 *
 * The engine was also written against a per-date availability schema that does
 * not exist. `availabilities` stores *ranges* (start_date, end_date, quantity,
 * price_per_night, status), so the old code queried a `date` column and read
 * is_available / price / min_stay / max_guests, all of which are absent. Booking
 * overlap checks queried `check_in` and `check_out`, which bookings does not
 * have either; the real columns are check_in_date and check_out_date. Every one
 * of those queries failed or returned nothing.
 */
class AvailabilityEngine
{
    /** Booking statuses that occupy inventory. */
    private const BLOCKING_STATUSES = ['confirmed', 'checked_in'];

    /**
     * Whether a specific bookable unit (room, villa, seat, etc.) is free.
     *
     * Checks for overlapping blocking bookings.
     */
    public function isAvailable(int $bookableId, string $bookableType, Carbon $checkIn, Carbon $checkOut): bool
    {
        $checkIn = $checkIn->copy()->startOfDay();
        $checkOut = $checkOut->copy()->endOfDay();

        $overlapping = Booking::query()
            ->where('bookable_id', $bookableId)
            ->where('bookable_type', $bookableType)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->exists();

        return ! $overlapping;
    }

    /**
     * Per-date availability for a bookable across a date range.
     *
     * Availability rows are ranges, so each date in the range is matched against
     * whichever availability record covers it. A date that no record covers is
     * still open — nothing has been configured to block or price it — and is
     * reported as such (`source => 'unconfigured'`) rather than invented.
     *
     * @return array<string, array{
     *   available: bool,
     *   price: float|null,
     *   currency: string|null,
     *   booked: int,
     *   capacity: int|null,
     *   source: string
     * }>
     */
    public function forBookable(AvailabilityAware $bookable, Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $ranges = $bookable->availabilities()
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get();

        $bookedPerDate = $this->bookedCounts($bookable, $start, $end);
        $currency = method_exists($bookable, 'getCurrency')
            ? $bookable->getCurrency()
            : null;

        $availability = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateStr = $date->toDateString();
            $booked = $bookedPerDate[$dateStr] ?? 0;

            $range = $ranges->first(
                fn ($record) => $record->start_date->lte($date) && $record->end_date->gte($date)
            );

            if ($range) {
                $capacity = (int) $range->quantity;
                $remaining = $capacity - $booked;

                $availability[$dateStr] = [
                    'available' => $range->status === 'available' && $remaining > 0,
                    'price' => $range->price_per_night === null
                        ? null
                        : (float) $range->price_per_night,
                    'currency' => $currency,
                    'booked' => $booked,
                    'capacity' => $capacity,
                    'source' => 'database',
                ];

                continue;
            }

            $availability[$dateStr] = [
                'available' => $booked === 0,
                'price' => null,
                'currency' => $currency,
                'booked' => $booked,
                'capacity' => null,
                'source' => 'unconfigured',
            ];
        }

        return $availability;
    }

    /**
     * Number of blocking bookings covering each date in a range.
     *
     * A stay of check_in_date..check_out_date-1 occupies those nights, so the
     * check-out day itself is free again.
     *
     * @return array<string, int>
     */
    protected function bookedCounts(AvailabilityAware $bookable, Carbon $start, Carbon $end): array
    {
        $bookings = $bookable->bookings()
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in_date', '<', $end)
            ->where('check_out_date', '>', $start)
            ->get();

        $counts = [];

        foreach ($bookings as $booking) {
            $from = $booking->check_in_date->copy()->startOfDay();
            $to = $booking->check_out_date->copy()->startOfDay()->subDay();

            if ($to->lt($from)) {
                $to = $from->copy();
            }

            foreach (CarbonPeriod::create($from, $to) as $date) {
                $key = $date->toDateString();
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
