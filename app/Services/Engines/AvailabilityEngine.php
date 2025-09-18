<?php

namespace App\Services\Engines;

use App\Contracts\Bookable;
use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AvailabilityEngine
{
    public function __construct(
        protected ?PricingEngine $pricingEngine = null, // Optional pricing engine
    ) {
        $this->pricingEngine ??= new PricingEngine();
    }

    /**
     * Check if a specific bookable unit (room, villa, seat, etc.) is available.
     */
    public function isAvailable(int $bookableId, string $bookableType, Carbon $checkIn, Carbon $checkOut): bool
    {
        // Normalize times to prevent half-day overlaps
        $checkIn  = $checkIn->copy()->startOfDay();
        $checkOut = $checkOut->copy()->endOfDay();

        return !Booking::where('bookable_id', $bookableId)
            ->where('bookable_type', $bookableType)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($checkIn, $checkOut) {
                // Overlap occurs if check-in is before the checkout
                // and checkout is after the check-in
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })
            ->exists();
    }

    /**
     * Build detailed availability data for a Bookable model.
     *
     * @return array<string, array{
     *   available: bool,
     *   price: int,
     *   currency: string,
     *   min_stay: int,
     *   max_guests: int,
     *   source: string
     * }>
     */
    public function forBookable(Bookable $bookable, Carbon $start, Carbon $end): array
    {
        // Normalize
        $start = $start->copy()->startOfDay();
        $end   = $end->copy()->endOfDay();

        // Get defined availabilities for this range
        $availabilities = $bookable->availabilities()
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $availability = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateStr = $date->toDateString();

            if ($availabilities->has($dateStr)) {
                $a = $availabilities[$dateStr];
                $availability[$dateStr] = [
                    'available'  => (bool) $a->is_available,
                    'price'      => $a->price,
                    'currency'   => $a->currency ?? 'USD',
                    'min_stay'   => $a->min_stay ?? 1,
                    'max_guests' => $a->max_guests ?? $bookable->getDefaultMaxGuests(),
                    'source'     => 'database',
                ];
            } else {
                // Fallback → dynamic pricing or base price
                $price = $this->pricingEngine->calculateDaily($bookable, $date);

                $availability[$dateStr] = [
                    'available'  => true,
                    'price'      => $price,
                    'currency'   => $bookable->getCurrency(),
                    'min_stay'   => 1,
                    'max_guests' => $bookable->getDefaultMaxGuests(),
                    'source'     => 'fallback',
                ];
            }
        }

        return $availability;
    }

    public function calculateDaily(Bookable $bookable, Carbon $date, int $guests = 1): float
    {
        return $this->pricingEngine->calculateDaily($bookable, $date, $guests);
    }

}
