<?php

namespace App\Services;

use App\Contracts\Interface\Bookable;
use Carbon\Carbon;

class PricingEngine
{
    /**
     * Calculate price for a given bookable and date.
     */
    public function calculate(Bookable $bookable, Carbon $date, int $guests = 1): int
    {
        $basePrice = $bookable->getBasePrice();
        $price = $basePrice;

        // 1. Seasonal pricing
        if ($seasonal = $bookable->seasonalRates()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first()) {
            $price = $seasonal->price;
        }

        // 2. Weekend surcharge
        if ($date->isWeekend()) {
            $price *= 1.10; // +10% on weekends
        }

        // 3. Per guest adjustment
        $includedGuests = $bookable->getIncludedGuests();
        if ($guests > $includedGuests) {
            $extraGuests = $guests - $includedGuests;
            $price += $extraGuests * $bookable->getExtraGuestFee();
        }

        // 4. Demand-based pricing (occupancy level)
        $occupancyRate = $this->getOccupancyRate($bookable, $date);
        if ($occupancyRate > 0.8) { // over 80% occupied
            $price *= 1.15; // +15%
        }

        // 5. Promotions / discounts
        if ($promo = $this->getActiveOffer($bookable, $date)) {
            $price -= ($price * $promo->discount_percent / 100);
        }

        return (int) round($price);
    }

    /**
     * Example: get occupancy rate for dynamic pricing.
     */
    protected function getOccupancyRate(Bookable $bookable, Carbon $date): float
    {
        $totalUnits = $bookable->rooms()->count();
        if ($totalUnits === 0) {
            return 0.0;
        }

        $bookedUnits = $bookable->bookings()
            ->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>=', $date)
            ->where('status', 'confirmed')
            ->count();

        return $bookedUnits / $totalUnits;
    }

    /**
     * Example: fetch active offers.
     */
    protected function getActiveOffer(Bookable $bookable, Carbon $date): ?object
    {
        return $bookable->offers()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
}
