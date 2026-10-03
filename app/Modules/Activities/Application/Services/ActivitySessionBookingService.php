<?php

namespace App\Modules\Activities\Application\Services;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Booking\Domain\Events\BookingCreated;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Pricing\Application\Services\PricingEngine;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivitySessionBookingService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly PaymentGateway $paymentGateway
    ) {}

    public function book(
        Activity $activity,
        ActivitySession $session,
        User $user,
        int $participants,
        ?string $idempotencyKey = null
    ): Booking {
        if ($participants < 1) {
            throw ValidationException::withMessages(['participants' => 'At least one participant is required.']);
        }

        if (! $activity->isPublished() || $session->activity_id !== $activity->id) {
            throw ValidationException::withMessages(['session' => 'The selected session is not bookable.']);
        }

        if ($idempotencyKey !== null && (trim($idempotencyKey) === '' || mb_strlen($idempotencyKey) > 255)) {
            throw ValidationException::withMessages(['idempotency_key' => 'The booking idempotency key must be between 1 and 255 characters.']);
        }

        if (! $user->guest) {
            throw ValidationException::withMessages(['guest' => 'Complete your guest profile before booking.']);
        }

        $idempotencyKey ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($activity, $session, $user, $participants, $idempotencyKey): Booking {
                $existing = Booking::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if ($existing->guest_id !== $user->guest->id) {
                        throw ValidationException::withMessages(['idempotency_key' => 'This booking key is already in use.']);
                    }

                    return $existing;
                }

                $session = ActivitySession::query()->lockForUpdate()->findOrFail($session->id);
                $existing = Booking::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    if ($existing->guest_id !== $user->guest->id) {
                        throw ValidationException::withMessages(['idempotency_key' => 'This booking key is already in use.']);
                    }

                    return $existing;
                }

                if (! $session->canFit($participants)) {
                    throw ValidationException::withMessages(['session' => 'This session does not have enough available places.']);
                }

                $startsAt = Carbon::instance($session->starts_at);
                if ($startsAt->lt(now()->addMinutes($activity->minimum_notice_minutes))) {
                    throw ValidationException::withMessages(['session' => 'The activity does not meet its minimum booking notice.']);
                }

                $option = $session->option;
                if ($session->activity_option_id !== null
                    && (! $option || ! $option->is_active || $option->activity_id !== $activity->id)) {
                    throw ValidationException::withMessages(['session' => 'The selected activity option is no longer available.']);
                }

                $maximumPartySize = $option?->max_participants ?? $activity->capacity ?? 1000;
                if ($participants > $maximumPartySize) {
                    throw ValidationException::withMessages(['participants' => "This activity option allows at most {$maximumPartySize} participants per booking."]);
                }

                $session->increment('booked_capacity', $participants);

                $bookable = $option ?? $activity;
                $checkIn = $startsAt->copy()->startOfDay();
                $checkOut = $checkIn->copy()->addDay();
                $total = $this->pricingEngine->calculateTotal($bookable, $checkIn, $checkOut, $participants);

                $booking = Booking::create([
                    'guest_id' => $user->guest->id,
                    'bookable_type' => $bookable->getMorphClass(),
                    'bookable_id' => $bookable->getId(),
                    'activity_session_id' => $session->id,
                    'destination_id' => $activity->destination_id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                    'guests_adults' => $participants,
                    'guests_children' => 0,
                    'total_amount' => $total,
                    'currency' => $bookable->getCurrency(),
                    'status' => 'pending',
                    'idempotency_key' => $idempotencyKey,
                    'confirmation_code' => strtoupper(Str::random(10)),
                ]);

                $this->paymentGateway->charge($booking);
                event(new BookingCreated($booking));

                return $booking;
            });
        } catch (QueryException $exception) {
            $existing = Booking::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing && $existing->guest_id === $user->guest->id) {
                return $existing;
            }

            throw $exception;
        }
    }
}
