<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class AvailabilityEngine
{
    /**
     * Check if a room is available for the given period.
     */
    public function isAvailable(int $roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        $conflict = Booking::where('room_id', $roomId)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function ($q) use ($checkIn, $checkOut) {
                          $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                      });
            })
            ->exists();

        return !$conflict;
    }
}
