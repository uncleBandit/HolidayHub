<?php

namespace App\Modules\Pricing\Domain\Contracts;

use Carbon\Carbon;

interface PricingStrategy
{
    /**
     * Calculate price for a bookable.
     */
    public function calculate(int $bookableId, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float;
}
