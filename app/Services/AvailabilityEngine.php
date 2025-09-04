<?php

namespace App\Services;

use App\Contracts\Interface\Bookable;
use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

    /**
     * Build availability data for any bookable model.
     *
     * @return array<string, array{available: bool, price: int}>
     */
    public function forBookable(Bookable $bookable, Carbon $start, Carbon $end): array
    {
        $availabilities = $bookable->availabilities()
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $availability = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateStr = $date->toDateString();

            if ($availabilities->has($dateStr)) {
                $a = $availabilities[$dateStr];
                $availability[$dateStr] = [
                    'available' => (bool) $a->is_available,
                    'price'     => $a->price,
                ];
            } else {
                // fallback → available at base price
                $availability[$dateStr] = [
                    'available' => true,
                    'price'     => $bookable->getBasePrice(),
                ];
            }
        }

        return $availability;
    }
}
