<?php

namespace App\Modules\Booking\Application\Services;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Booking\Domain\ValueObjects\PolicyResult;
use Illuminate\Support\Carbon;

class PolicyEngine
{
    public function __construct(private readonly CancellationTerms $terms) {}

    /**
     * Whether the guest may cancel this booking now, and what they get back.
     *
     * The refund comes from the listing's own cancellation policy (see
     * CancellationTerms) and is returned in the result's context:
     * percent, amount, currency, paid, text, summary and free_until.
     */
    public function canCancel(Booking $booking): PolicyResult
    {
        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return PolicyResult::deny("This booking is {$booking->status} and can no longer be cancelled.", 'STATUS');
        }

        // A stay or experience inside a journey is cancelled with the journey,
        // never on its own: the guest booked the whole trip as one.
        if ($booking->package_booking_id !== null) {
            return PolicyResult::deny('This is part of a journey. Cancel the journey booking instead.', 'PACKAGE_COMPONENT');
        }

        $quote = $this->terms->quote($booking);

        if ($quote['started']) {
            return PolicyResult::deny('This booking has already started, so it can no longer be cancelled online. Contact the host, or our support team at '.app(\App\Modules\Administration\Application\Services\PlatformSettings::class)->get('support.email').'.', 'STARTED', $quote);
        }

        if ($this->isInBlackoutPeriod($quote['start'])) {
            return PolicyResult::deny('Cancellations are not allowed during blackout periods.', 'BLACKOUT', $quote);
        }

        return PolicyResult::allow($quote['summary'], 'ALLOWED', $quote);
    }

    /**
     * Determine if a booking can be modified.
     */
    public function canModify(Booking $booking): PolicyResult
    {
        // Rule 1: Only modifiable when not already checked-in or cancelled
        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return PolicyResult::deny("This booking cannot be modified once {$booking->status}.");
        }

        // Rule 2: Restrict last-minute changes
        $cutoffHours = config('policies.modify_cutoff_hours', 24);
        if (now()->greaterThan($booking->check_in_date->copy()->subHours($cutoffHours))) {
            return PolicyResult::deny("Modifications must be made at least {$cutoffHours} hours before check-in.");
        }

        return PolicyResult::allow('Booking can be modified.');
    }

    /**
     * Helper to check blackout periods (holidays, peak season, etc.).
     */
    protected function isInBlackoutPeriod(\DateTimeInterface $date): bool
    {
        // Admins set blackout periods on the Platform settings page; any in
        // config/policies.php still apply as well.
        $blackouts = array_merge(
            (array) app(\App\Modules\Administration\Application\Services\PlatformSettings::class)->get('bookings.blackouts', []),
            (array) config('policies.blackout_dates', []),
        );

        foreach ($blackouts as $range) {
            $start = $range['start'] ?? $range[0] ?? null;
            $end = $range['end'] ?? $range[1] ?? null;
            if ($start && $end && Carbon::instance($date)->between(Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay())) {
                return true;
            }
        }

        return false;
    }
}
