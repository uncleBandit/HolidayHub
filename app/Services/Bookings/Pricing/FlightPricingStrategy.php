<?php

namespace App\Services\Bookings\Pricing;

use App\Models\Flight;
use Carbon\Carbon;

class FlightPricingStrategy implements PricingStrategy
{
    public function calculate(int $bookableId, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        $flight = Flight::findOrFail($bookableId);
        return round($flight->base_fare * $guests, 2);
    }
}
