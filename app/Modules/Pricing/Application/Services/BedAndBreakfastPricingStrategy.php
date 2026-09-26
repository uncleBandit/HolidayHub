<?php

namespace App\Modules\Pricing\Application\Services;

use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\Pricable;
use Carbon\Carbon;

class BedAndBreakfastPricingStrategy implements PricingStrategy
{
    protected PricingEngine $pricingEngine;

    public function __construct(PricingEngine $pricingEngine)
    {
        $this->pricingEngine = $pricingEngine;
    }

    /**
     * Total price for a Bed & Breakfast booking.
     *
     * This class went from being a competing pricing implementation to a thin
     * delegate. Keeping it means the PricingStrategy contract it implements still
     * has one honest implementation, and callers who resolved it by class do not
     * start silently using different numbers. Every calculation — the seasonal
     * `rate` column, weekend surcharge, extra guests, occupancy, offers — is done
     * by PricingEngine. The old version read `seasonalRate->price`, a column
     * that does not exist, so any seasonal rate collapsed the nightly price to
     * zero.
     */
    public function calculate(
        int $bookableId,
        ?int $roomId,
        Carbon $checkIn,
        Carbon $checkOut,
        int $guests
    ): float {
        $bAndB = \App\Modules\Accommodation\Domain\Models\BedAndBreakfast::findOrFail($bookableId);

        return $this->pricingEngine->calculateTotal($bAndB, $checkIn, $checkOut, $guests);
    }

    public function calculateDaily(Bookable&Pricable $bAndB, Carbon $date, int $guests = 1): float
    {
        return $this->pricingEngine->calculateDaily($bAndB, $date, $guests);
    }

    /**
     * Get the occupancy rate for a specific date.
     */
    // protected function getOccupancyRate(Bookable&Pricable $bAndB, Carbon $date): float
    // {
    // $totalRooms = $bAndB->rooms()->count() ?: 1;

    // $bookedRooms = $bAndB->bookings()
    //     ->whereDate('check_in', '<=', $date)
    //    ->whereDate('check_out', '>', $date)
    //    ->whereIn('status', ['confirmed', 'checked_in'])
    //    ->count();

    // return min($bookedRooms / $totalRooms, 1.0);
    // }
}
