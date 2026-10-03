<?php

namespace App\Modules\Booking\Application\Services;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityOption;
use App\Modules\Availability\Application\Services\AvailabilityEngine;
use App\Modules\Booking\Domain\Events\BookingCancelled;
use App\Modules\Booking\Domain\Events\BookingCreated;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Pricing\Application\Services\PricingEngine;
use App\Shared\Domain\Contracts\Bookable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class BookingManager
{
    public function __construct(
        private AvailabilityEngine $availabilityEngine,
        private PolicyEngine $policyEngine,
        private PaymentGateway $paymentGateway,
        private PricingEngine $pricingEngine
    ) {}

    /**
     * Create a new booking for any bookable model.
     *
     * @throws \DomainException if availability or payment fails
     */
    public function create(array $data, int $userId, ?string $idempotencyKey = null): Booking
    {
        /** @var \App\Modules\Identity\Domain\Models\User $user */
        $user = \App\Modules\Identity\Domain\Models\User::findOrFail($userId);

        // 🔑 Ensure user has a guest profile
        if (! $user->guest) {
            throw new \DomainException('You must complete your guest profile before booking.');
        }

        $guest = $user->guest;

        /** @var Bookable $bookable */
        $bookable = $this->resolveBookable($data['bookable_type'], $data['bookable_id']);

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $guests = $data['guests'] ?? $bookable->getIncludedGuests();

        // Prevent duplicate booking requests. This must run before the
        // availability check: once the first submission succeeds it occupies
        // inventory, so a retry of the same request would otherwise be rejected
        // as unavailable instead of returning the original booking.
        $idempotencyKey ??= Str::uuid()->toString();
        if ($existing = Booking::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        // The per-model isAvailable() implementations diverged from the schema
        // (Package and Experience query columns bookings does not have), so the
        // overlap check that gates every booking goes through the one engine
        // that implements it correctly.
        if (! $this->availabilityEngine->isAvailable($bookable->getId(), $bookable->getMorphClass(), $checkIn, $checkOut)) {
            throw new \DomainException('Selected item is not available for the given dates.');
        }

        $totalPrice = $this->calculatePrice($bookable, $checkIn, $checkOut, $guests);

        return DB::transaction(function () use ($bookable, $guest, $checkIn, $checkOut, $guests, $totalPrice, $idempotencyKey) {
            $booking = Booking::create([
                'guest_id' => $guest->id,        // ✅ link to Guest profile
                // The enforced morph map stores the bookable alias, not the FQCN.
                // Storing get_class() here made every morph lookup for this
                // booking miss, and with enforceMorphMap() on it even throws.
                'bookable_type' => $bookable->getMorphClass(),
                'bookable_id' => $bookable->getId(),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'guests_adults' => $guests,
                'guests_children' => 0,                 // adjust if needed
                'total_amount' => $totalPrice,
                'currency' => $guest->preferred_currency ?? 'USD',
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'confirmation_code' => strtoupper(Str::random(10)), // unique booking ref
            ]);

            $this->paymentGateway->charge($booking);

            event(new BookingCreated($booking));

            return $booking;
        });
    }

    /**
     * Cancel a booking if allowed by policies.
     *
     * bookings has no user_id column; the owner is guest_id. This method used to
     * query user_id, so it always failed.
     */
    public function cancel(int $bookingId, int $userId): void
    {
        $user = \App\Modules\Identity\Domain\Models\User::findOrFail($userId);

        if (! $user->guest) {
            throw new \DomainException('You must complete your guest profile before booking.');
        }

        $booking = Booking::where('guest_id', $user->guest->id)->findOrFail($bookingId);

        if (! $this->policyEngine->canCancel($booking)) {
            throw new \DomainException('This booking cannot be cancelled due to policy restrictions.');
        }

        DB::transaction(function () use ($booking) {
            $session = $booking->activitySession()->lockForUpdate()->first();
            if ($session) {
                $participants = $booking->guests_adults + $booking->guests_children;
                $released = $session->newQuery()
                    ->whereKey($session->id)
                    ->where('booked_capacity', '>=', $participants)
                    ->decrement('booked_capacity', $participants);

                if ($released !== 1) {
                    throw new RuntimeException('Activity session capacity is inconsistent with the booking being cancelled.');
                }
            }

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
        $user = \App\Modules\Identity\Domain\Models\User::findOrFail($userId);

        return Booking::with('bookable')
            ->where('guest_id', $user->guest->id)
            ->latest();
    }

    /**
     * Get global query builder for admins
     */
    public function query()
    {
        return Booking::with('bookable')->newQuery();
    }

    /**
     * Preview the total price for a given booking without persisting.
     *
     * @param  string  $type  The bookable type (morph alias)
     * @param  int  $id  The bookable ID
     * @param  int|null  $roomId  Ignored; nightly pricing is per-bookable.
     * @param  Carbon  $checkIn  Start date
     * @param  Carbon  $checkOut  End date
     * @param  int  $guests  Number of guests
     *
     * @throws InvalidArgumentException if bookable type is unsupported
     * @throws RuntimeException if calculation fails
     */
    public function previewPrice(string $type, int $id, ?int $roomId, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        return $this->pricingEngine->calculateTotal(
            $this->resolveBookable($type, $id),
            $checkIn,
            $checkOut,
            $guests
        );
    }

    /**
     * Calculate price based on bookable logic and guests.
     *
     * Previously this summed $bookable->getPriceForDate(), which read a base_price
     * column that does not exist on room_types and never saw seasonal rates or
     * offers — so the total saved on create() disagreed with the quote shown by
     * previewPrice(). Both now run the same canonical calculator.
     */
    private function calculatePrice(Bookable $bookable, Carbon $checkIn, Carbon $checkOut, int $guests): float
    {
        return $this->pricingEngine->calculateTotal($bookable, $checkIn, $checkOut, $guests);
    }

    /**
     * Resolve a bookable instance from morph alias + ID.
     *
     * The old implementation was a hardcoded map that missed activity and
     * experience, and mapped "flight" to a model that is not even Bookable — so
     * it could not resolve what the calendar dispatches (getMorphClass() aliases).
     * The enforced morph map is the single source of truth for both.
     */
    private function resolveBookable(string $type, int $id): Bookable
    {
        $class = Relation::getMorphedModel($type);

        if (! $class || ! is_a($class, Bookable::class, true)) {
            throw new InvalidArgumentException("Unsupported bookable type: {$type}");
        }

        $bookable = $class::findOrFail($id);

        if (! $bookable instanceof Bookable) {
            throw new \LogicException('Bookable class must implement '.Bookable::class);
        }

        if ($bookable instanceof Activity || $bookable instanceof ActivityOption) {
            throw new \DomainException('Activity bookings must select and reserve a scheduled session.');
        }

        $accommodation = match (true) {
            $bookable instanceof Hotel,
            $bookable instanceof BedAndBreakfast,
            $bookable instanceof Villa => $bookable->accommodation,
            $bookable instanceof Room => $bookable->hotel?->accommodation,
            $bookable instanceof RoomType => $bookable->hotel?->accommodation,
            default => null,
        };

        $requiresPublishedAccommodation = $bookable instanceof Hotel
            || $bookable instanceof BedAndBreakfast
            || $bookable instanceof Villa
            || $bookable instanceof Room
            || ($bookable instanceof RoomType && $bookable->hotel !== null);

        if ($requiresPublishedAccommodation
            && ! $accommodation?->isPublished()) {
            throw new \DomainException('This accommodation is not published and cannot be booked.');
        }

        return $bookable;
    }
}
