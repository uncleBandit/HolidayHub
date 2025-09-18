<?php

namespace App\Services\Bookings\Pricing;

use App\Models\Package;
use Carbon\Carbon;

class PackagePricingStrategy implements PricingStrategy
{
    public function calculate(int $bookableId, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        $package = Package::findOrFail($bookableId);
        return round($package->price_per_guest * $guests, 2);
    }
}
