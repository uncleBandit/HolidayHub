<?php

namespace App\Services\Bookings;

use App\Contracts\Bookable;
use App\Events\BookingCreated;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\RoomType;
use App\Services\Payments\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use App\Services\Engines\AvailabilityEngine;
use App\Services\Engines\PolicyEngine;

class BookingManager
{
    public function __construct(
        private AvailabilityEngine $availabilityEngine,
        private PolicyEngine $policyEngine,
        private PaymentGateway $paymentGateway
    ) {}

    /**
     * Create a new booking for any bookable model.
     *
     * @throws \DomainException if availability or payment fails
     */
    public function create(array $data, int $userId, ?string $idempotencyKey = null): Booking
    {
        /** @var Bookable $bookable */
        $bookable = $this->resolveBookable($data['bookable_type'], $data['bookable_id']);

        $checkIn  = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $guests   = $data['guests'] ?? $bookable->getIncludedGuests();

        // Availability check
        if (! $bookable->isAvailable($checkIn->toDateString(), $checkOut->toDateString())) {
            throw new \DomainException("Selected item is not available for the given dates.");
        }

        // Prevent duplicate booking requests (idempotency)
        $idempotencyKey ??= Str::uuid()->toString();
        if ($existing = Booking::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        // Calculate total price
        $totalPrice = $this->calculatePrice($bookable, $checkIn, $checkOut, $guests);

        return DB::transaction(function () use ($bookable, $userId, $checkIn, $checkOut, $guests, $totalPrice, $idempotencyKey) {
            $booking = Booking::create([
                'bookable_type'   => get_class($bookable),
                'bookable_id'     => $bookable->getId(),
                'user_id'         => $userId,
                'check_in'        => $checkIn,
                'check_out'       => $checkOut,
                'guests'          => $guests,
                'total_price'     => $totalPrice,
                'status'          => 'pending_payment',
                'idempotency_key' => $idempotencyKey,
            ]);

            // Initiate payment
            $this->paymentGateway->charge($booking);

            // Trigger events
            event(new BookingCreated($booking));

            return $booking;
        });
    }

    /**
     * Cancel a booking if allowed by policies.
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
     * Get bookings for a user
     */
    public function forUser(int $userId)
    {
        return Booking::with('bookable')->where('user_id', $userId)->latest();
    }

    /**
     * Get global query builder for admins
     */
    public function query()
    {
        return Booking::with('bookable')->newQuery();
    }

    /**
     * Preview the total price for a given booking type without persisting.
     *
     * @param  string     $type      The bookable type (hotel, villa, tour, flight, etc.)
     * @param  int        $id        The bookable ID
     * @param  int|null   $roomId    Optional room ID (relevant for hotels/villas)
     * @param  Carbon     $checkIn   Start date
     * @param  Carbon     $checkOut  End date
     * @param  int        $guests    Number of guests
     *
     * @return float
     *
     * @throws InvalidArgumentException if bookable type is unsupported
     * @throws RuntimeException if calculation fails
     */
    public function previewPrice(string $type, int $id, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        $pricing = app(\App\Services\Bookings\Pricing\PricingContext::class);
        return $pricing->calculate($type, $id, $roomId, $checkIn, $checkOut, $guests);
    }


    /**
     * Calculate price based on bookable logic and guests
     */
    private function calculatePrice(Bookable $bookable, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        $days = max(1, $checkIn->diffInDays($checkOut));

        $total = 0.0;
        for ($i = 0; $i < $days; $i++) {
            $date = $checkIn->copy()->addDays($i)->toDateString();
            $total += $bookable->getPriceForDate($date);
        }

        // Optional: extra guest fee
        $extraGuests = max(0, $guests - $bookable->getDefaultMaxGuests());
        if ($extraGuests > 0) {
            $total += $extraGuests * 20 * $days; // Example: $20 per extra guest per day
        }

        return round($total, 2);
    }

    /**
     * Resolve a bookable instance from type + ID
     */
    private function resolveBookable(string $type, int $id): Bookable
    {
        $map = [
            'hotel' => \App\Models\Hotel::class,
            'villa' => \App\Models\Villa::class,
            'package'  => \App\Models\Package::class,
            'flight'=> \App\Models\Flight::class,
            'bed_and_breakfast' => \App\Models\BedAndBreakfast::class,
            'room_type'=>RoomType::class,
        ];

        if (! isset($map[$type])) {
            throw new \InvalidArgumentException("Unsupported bookable type: {$type}");
        }

        $bookable = $map[$type]::findOrFail($id);

        if (! $bookable instanceof Bookable) {
            throw new \LogicException("Bookable class must implement App\Contracts\Bookable");
        }

        return $bookable;
    }
}
