<?php

namespace App\Services\Bookings\Pricing;

use Carbon\Carbon;

interface PricingStrategy
{
    /**
     * Calculate price for a bookable.
     *
     * @param int        $bookableId
     * @param int|null   $roomId
     * @param Carbon     $checkIn
     * @param Carbon     $checkOut
     * @param int        $guests
     *
     * @return float
     */
    public function calculate(int $bookableId, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float;
}
