<?php

namespace App\Shared\Domain\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Whether a bookable can be reserved for a window of dates.
 *
 * Owned by the Availability module in spirit, but declared here so that
 * implementing it does not make a model import from Availability. Availability
 * depends on this contract; nothing outside Availability needs to.
 *
 * The `isAvailable()` implementation on the models is a thin wrapper over the
 * same overlapping-booking check AvailabilityEngine::isAvailable() performs.
 * That duplication is left in place for now — collapsing it means Booking asks
 * AvailabilityEngine directly instead of calling through the model, which is a
 * separate behavioural change.
 */
interface AvailabilityAware
{
    /** Recorded availability rows for this bookable. */
    public function availabilities(): Relation;

    public function isAvailable(string $checkIn, string $checkOut): bool;
}
