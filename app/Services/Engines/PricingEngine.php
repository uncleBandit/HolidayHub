<?php

namespace App\Services\Engines;

use App\Contracts\Bookable;
use Carbon\Carbon;

class PricingEngine
{
    /**
     * Calculate total price for a given bookable across a date range.
     *
     * @param Bookable $bookable
     * @param Carbon   $checkIn
     * @param Carbon   $checkOut
     * @param int      $guests
     *
     * @return float
     */
    public function calculateTotal(Bookable $bookable, Carbon $checkIn, Carbon $checkOut, int $guests = 1): float
    {
        $days = max(1, $checkIn->diffInDays($checkOut));
        $total = 0.0;

        for ($i = 0; $i < $days; $i++) {
            $date = $checkIn->copy()->addDays($i);
            $total += $this->calculateDaily($bookable, $date, $guests);
        }

        return round($total, 2);
    }

    /**
     * Calculate daily price for a bookable.
     *
     * @param Bookable $bookable
     * @param Carbon   $date
     * @param int      $guests
     *
     * @return float
     */
    public function calculateDaily(Bookable $bookable, Carbon $date, int $guests = 1): float
    {
        $price = $bookable->getBasePrice();

        // 1. Seasonal pricing
        if ($seasonalRate = $bookable->seasonalRates()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first()) {
            $price = $seasonalRate->price;
        }

        // 2. Weekend / holiday surcharge
        if ($date->isWeekend()) {
            $price *= 1.10; // +10%
        }
        if ($holidaySurcharge = $this->getHolidaySurcharge($bookable, $date)) {
            $price *= 1 + $holidaySurcharge;
        }

        // 3. Per guest adjustment
        $includedGuests = $bookable->getIncludedGuests();
        if ($guests > $includedGuests) {
            $extraGuests = $guests - $includedGuests;
            $price += $extraGuests * $bookable->getExtraGuestFee();
        }

        // 4. Demand-based dynamic pricing (occupancy)
        $occupancyRate = $this->getOccupancyRate($bookable, $date);
        if ($occupancyRate > 0.8) {
            $price *= 1.15; // +15% if occupancy > 80%
        }

        // 5. Active promotions / discounts
        if ($promo = $this->getActiveOffer($bookable, $date)) {
            $price -= ($price * $promo->discount_percent / 100);
        }

        return round($price, 2);
    }

    /**
     * Calculate occupancy rate for a given date.
     */
    protected function getOccupancyRate(Bookable $bookable, Carbon $date): float
    {
        $totalUnits = $bookable->rooms()->count() ?: 1;

        $bookedUnits = $bookable->bookings()
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>', $date)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        return min($bookedUnits / $totalUnits, 1.0);
    }

    /**
     * Fetch active offers for a specific date.
     */
    protected function getActiveOffer(Bookable $bookable, Carbon $date): ?object
    {
        return $bookable->offers()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('active', true)
            ->first();
    }

    /**
     * Optional: check for holiday surcharges.
     */
   // protected function getHolidaySurcharge(Bookable $bookable, Carbon $date): float
   // {
        // Example: could be extended to fetch holiday multipliers from DB
     //   $holidays = $bookable->holidaySurcharges()->where('date', $date->toDateString())->first();
      //  return $holidays->surcharge ?? 0.0;
   // }
}
