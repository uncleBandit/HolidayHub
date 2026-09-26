<?php

namespace App\Modules\Booking\Application\Services;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Booking\Domain\ValueObjects\PolicyResult;
use Illuminate\Support\Carbon;

class PolicyEngine
{
    /**
     * Evaluate if a booking can be cancelled, returning result + reason.
     */
    public function canCancel(Booking $booking): PolicyResult
    {
        // Rule 1: Status must allow cancellation
        if (! in_array($booking->status, ['pending_payment', 'confirmed'])) {
            return PolicyResult::deny("This booking cannot be cancelled once {$booking->status}.");
        }

        // Rule 2: Cancellation cutoff (configurable per provider or default)
        $cutoffHours = $booking->provider->cancellation_cutoff_hours ?? config('policies.default_cutoff_hours', 48);
        $cutoffTime = $booking->check_in->copy()->subHours($cutoffHours);

        if (now()->greaterThan($cutoffTime)) {
            return PolicyResult::deny("Cancellations must be made at least {$cutoffHours} hours before check-in.");
        }

        // Rule 3: Promo / loyalty restrictions
        if ($booking->promo_code && $booking->promo->non_refundable) {
            return PolicyResult::deny('This booking was made with a non-refundable promotion.');
        }

        // Rule 4: Special blackout periods
        if ($this->isInBlackoutPeriod($booking->check_in)) {
            return PolicyResult::deny('Cancellations are not allowed during blackout periods.');
        }

        // ✅ Passed all checks
        return PolicyResult::allow('Booking can be cancelled.');
    }

    /**
     * Determine if a booking can be modified.
     */
    public function canModify(Booking $booking): PolicyResult
    {
        // Rule 1: Only modifiable when not already checked-in or cancelled
        if (! in_array($booking->status, ['pending_payment', 'confirmed'])) {
            return PolicyResult::deny("This booking cannot be modified once {$booking->status}.");
        }

        // Rule 2: Restrict last-minute changes
        $cutoffHours = config('policies.modify_cutoff_hours', 24);
        if (now()->greaterThan($booking->check_in->copy()->subHours($cutoffHours))) {
            return PolicyResult::deny("Modifications must be made at least {$cutoffHours} hours before check-in.");
        }

        return PolicyResult::allow('Booking can be modified.');
    }

    /**
     * Helper to check blackout periods (holidays, peak season, etc.).
     */
    protected function isInBlackoutPeriod(Carbon $date): bool
    {
        $blackouts = config('policies.blackout_dates', []); // e.g. ['2025-12-20' => '2026-01-05']
        foreach ($blackouts as $range) {
            [$start, $end] = $range;
            if ($date->between(Carbon::parse($start), Carbon::parse($end))) {
                return true;
            }
        }

        return false;
    }
}
