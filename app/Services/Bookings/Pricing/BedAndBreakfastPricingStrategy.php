<?php

namespace App\Services\Bookings\Pricing;

use App\Contracts\Bookable;
use App\Services\Engines\PricingEngine;
use Carbon\Carbon;

class BedAndBreakfastPricingStrategy implements PricingStrategy
{
    protected PricingEngine $pricingEngine;

    public function __construct(PricingEngine $pricingEngine)
    {
        $this->pricingEngine = $pricingEngine;
    }

    /**
     * Calculate total price for a Bed & Breakfast booking.
     *
     * @param Bookable $bAndB
     * @param Carbon   $checkIn
     * @param Carbon   $checkOut
     * @param int      $guests
     *
     * @return float
     */
    public function calculate(
    int $bookableId,
    ?int $roomId,
    Carbon $checkIn,
    Carbon $checkOut,
    int $guests
    ): float {
        /** @var \App\Contracts\Bookable $bAndB */
        $bAndB = \App\Models\BedAndBreakfast::findOrFail($bookableId);

        $days = max(1, $checkIn->diffInDays($checkOut));
        $total = 0.0;

        for ($i = 0; $i < $days; $i++) {
            $date = $checkIn->copy()->addDays($i);
            $total += $this->calculateDaily($bAndB, $date, $guests);
        }

        return round($total, 2);
    }


    /**
     * Calculate the price for a single night.
     *
     * @param Bookable $bAndB
     * @param Carbon   $date
     * @param int      $guests
     *
     * @return float
     */
    public function calculateDaily(Bookable $bAndB, Carbon $date, int $guests = 1): float
    {
        // Start from base price
        $price = $bAndB->getBasePrice();

        // 1. Seasonal rate adjustment
        $seasonalRate = $bAndB->seasonalRates()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($seasonalRate) {
            $price = $seasonalRate->price;
        }

        // 2. Weekend surcharge
        if ($date->isWeekend()) {
            $price *= 1.10; // +10%
        }

        // 3. Holiday surcharge
        //$holidaySurcharge = $bAndB->holidaySurcharges()->where('date', $date->toDateString())->first();
        //if ($holidaySurcharge) {
         //   $price *= 1 + ($holidaySurcharge->surcharge ?? 0.0);
        //}

        // 4. Extra guest fees
       // $includedGuests = $bAndB->getIncludedGuests();
        //if ($guests > $includedGuests) {
         //   $extraGuests = $guests - $includedGuests;
          //  $price += $extraGuests * $bAndB->getExtraGuestFee();
        //}

        // 5. Occupancy-based dynamic pricing
       // $occupancyRate = $this->getOccupancyRate($bAndB, $date);
       // if ($occupancyRate > 0.75) { // threshold for B&B
       //     $price *= 1.12; // +12% for high demand
       // }

        // 6. Active promotions / discounts
        $promo = $bAndB->offers()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('active', true)
            ->first();

        if ($promo) {
            $price -= ($price * $promo->discount_percent / 100);
        }

        return round($price, 2);
    }

    /**
     * Get the occupancy rate for a specific date.
     */
    //protected function getOccupancyRate(Bookable $bAndB, Carbon $date): float
    //{
       // $totalRooms = $bAndB->rooms()->count() ?: 1;

       // $bookedRooms = $bAndB->bookings()
       //     ->whereDate('check_in', '<=', $date)
        //    ->whereDate('check_out', '>', $date)
        //    ->whereIn('status', ['confirmed', 'checked_in'])
        //    ->count();

       // return min($bookedRooms / $totalRooms, 1.0);
   // }
}
