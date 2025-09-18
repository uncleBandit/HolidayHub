<?php

namespace App\Services\Bookings\Pricing;

use App\Models\Hotel;
use Carbon\Carbon;

class RoomPricingStrategy implements PricingStrategy
{
    public function calculate(int $bookableId, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        $room = Hotel::findOrFail($roomId ?? $bookableId);
        $days = max(1, $checkIn->diffInDays($checkOut));
        $price = $room->base_price * $days;

        $extraGuests = max(0, $guests - $room->capacity);
        if ($extraGuests > 0) {
            $price += $extraGuests * ($room->extra_guest_fee ?? 20) * $days;
        }

        return round($price, 2);
    }
}
