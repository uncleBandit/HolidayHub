<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AvailabilityEngine;

class BookingManager
{
    public function __construct(private AvailabilityEngine $availabilityEngine) {}

    /**
     * Create a new booking if available.
     */
    public function create(array $data): Booking
    {
        $hotel = Hotel::findOrFail($data['hotel_id']);
        $room = Room::where('hotel_id', $hotel->id)->findOrFail($data['room_id']);

        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        // Ensure room is free
        if (!$this->availabilityEngine->isAvailable($room->id, $checkIn, $checkOut)) {
            throw new \Exception("Room not available for the selected dates.");
        }

        return DB::transaction(function () use ($data, $room) {
            return Booking::create([
                'hotel_id'   => $room->hotel_id,
                'room_id'    => $room->id,
                'user_id'    => $data['user_id'],
                'check_in'   => $data['check_in'],
                'check_out'  => $data['check_out'],
                'guests'     => $data['guests'] ?? 1,
                'total_price'=> $room->price_per_night * Carbon::parse($data['check_in'])->diffInDays(Carbon::parse($data['check_out'])),
                'status'     => 'confirmed',
            ]);
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancel(int $bookingId): void
    {
        $booking = Booking::findOrFail($bookingId);

        // Future: add refund logic here
        $booking->update(['status' => 'cancelled']);
    }

    /**
     * List user bookings.
     */
    public function forUser(int $userId)
    {
        return Booking::with('hotel', 'room')
            ->where('user_id', $userId)
            ->latest()
            ->paginate(10);
    }
}

