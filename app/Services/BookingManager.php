<?php

namespace App\Services;

use App\Contracts\BookableInterface;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingManager
{
    public function __construct(
        private AvailabilityEngine $availabilityEngine,
       // private PolicyEngine $policyEngine,        // e.g., cancellation/refund rules
       // private PaymentGateway $paymentGateway     // abstraction for Stripe, PayPal, etc.
    ) {}

    /**
     * Create a new booking for any bookable model.
     *
     * @throws \DomainException if availability or payment fails.
     */
    public function create(array $data, int $userId, ?string $idempotencyKey = null): Booking
    {
        $bookableClass = $data['bookable_type']; // e.g., App\Models\Room
        /** @var BookableInterface $bookable */
        $bookable = $bookableClass::findOrFail($data['bookable_id']);

        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $guests   = $data['guests'] ?? 1;

        // Ensure availability
        if (! $bookable->isAvailable($checkIn, $checkOut, $guests)) {
            throw new \DomainException("Selected item is not available for the given dates.");
        }

        // Prevent duplicate booking requests (idempotency)
        $idempotencyKey = $idempotencyKey ?? Str::uuid()->toString();
        if ($existing = Booking::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        // Calculate total price (delegated to the bookable/pricing strategy)
        $totalPrice = $bookable->calculatePrice($checkIn, $checkOut, $data);

        return DB::transaction(function () use ($bookable, $userId, $checkIn, $checkOut, $guests, $totalPrice, $idempotencyKey) {
            $booking = Booking::create([
                'bookable_type'  => get_class($bookable),
                'bookable_id'    => $bookable->id,
                'user_id'        => $userId,
                'check_in'       => $checkIn,
                'check_out'      => $checkOut,
                'guests'         => $guests,
                'total_price'    => $totalPrice,
                'status'         => 'pending_payment',
                'idempotency_key'=> $idempotencyKey,
            ]);

            // Initiate payment via gateway (async-safe)
           // $this->paymentGateway->initiate($booking);

            // Fire event for async listeners (emails, calendars, partner syncs, etc.)
            event(new BookingCreated($booking));

            return $booking;
        });
    }

    /**
     * Cancel a booking if allowed by policies.
     *
     * @throws \DomainException if cancellation is not permitted.
     */
    public function cancel(int $bookingId, int $userId): void
    {
        $booking = Booking::where('user_id', $userId)->findOrFail($bookingId);

        if (! $this->policyEngine->canCancel($booking)) {
            throw new \DomainException("This booking cannot be cancelled due to policy restrictions.");
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);
            $this->paymentGateway->refund($booking);

            event(new BookingCancelled($booking));
        });
    }

    /**
     * Get bookings for a user.
     */
    public function forUser(int $userId)
    {
        return Booking::with('bookable')
            ->where('user_id', $userId)
            ->latest();
    }

    /**
     * Get global query builder for admins.
     */
    public function query()
    {
        return Booking::with('bookable')->newQuery();
    }

    public function previewPrice(
    string $type,
    int $id,
    ?int $roomId,
    Carbon $checkIn,
    Carbon $checkOut,
    int $guests
     ): float {
    $days = max(1, $checkIn->diffInDays($checkOut)); // at least 1 day

    return match ($type) {
        'hotel', 'villa' => $this->calculateRoomPrice($id, $roomId, $days, $guests, $checkIn, $checkOut),
        'tour'           => $this->calculateTourPrice($id, $guests),
        'flight'         => $this->calculateFlightPrice($id, $guests),
        default          => throw new \InvalidArgumentException("Unsupported bookable type: {$type}"),
    };
    }

    /**
 * Calculate price for hotel/villa rooms.
 */
private function calculateRoomPrice(
    int $hotelId,
    ?int $roomId,
    int $days,
    int $guests,
    Carbon $checkIn,
    Carbon $checkOut
): float {
    if (!$roomId) {
        throw new \InvalidArgumentException("Room ID is required for hotels and villas.");
    }

    $room = \App\Models\Room::where('hotel_id', $hotelId)->findOrFail($roomId);

    // Example: apply seasonal rates
    $basePrice = $room->price_per_night * $days;

    if ($this->isHighSeason($checkIn, $checkOut)) {
        $basePrice *= 1.25; // 25% surcharge
    }

    // Add extra guest fee if exceeds base occupancy
    if ($guests > $room->max_guests) {
        $extraGuests = $guests - $room->max_guests;
        $basePrice += $extraGuests * $room->extra_guest_fee * $days;
    }

    return round($basePrice, 2);
}

    /**
     * Calculate tour price per guest.
     */
    private function calculateTourPrice(int $tourId, int $guests): float
    {
    $tour = \App\Models\Package::findOrFail($tourId);

    $price = $tour->price_per_person * $guests;

    // Example: group discount for 5+
    if ($guests >= 5) {
        $price *= 0.9; // 10% discount
    }

    return round($price, 2);
    }

    /**
     * Calculate flight price per seat.
     */
    private function calculateFlightPrice(int $flightId, int $guests): float
    {
    $flight = \App\Models\Flight::findOrFail($flightId);

    $price = $flight->base_price * $guests;

    // Example: dynamic pricing if seats < 10
    if ($flight->available_seats < 10) {
        $price *= 1.15; // 15% surge
    }

    return round($price, 2);
    }

        /**
         * Detect high season dates (example: July–August, December).
         */
    private function isHighSeason(Carbon $checkIn, Carbon $checkOut): bool
    {
    $highSeasonMonths = [7, 8, 12]; // July, Aug, Dec

    return $checkIn->isSameMonth($checkOut) &&
        in_array($checkIn->month, $highSeasonMonths);
    }


}
