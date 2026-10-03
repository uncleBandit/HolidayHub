<?php

namespace App\Shared\Domain\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * A bookable made up of separately bookable units, such as rooms.
 *
 * Occupancy-based pricing needs to know how many units exist, and how many are
 * booked, in order to work out demand. Only some bookables have that shape, and
 * PricingEngine used to find out by calling `$bookable->rooms()` unconditionally
 * — a relation that exists on Hotel and RoomType and nowhere else, so pricing a
 * Villa, BedAndBreakfast, Activity or Package threw.
 *
 * Opting in explicitly means an unknown capacity is a normal, handled case
 * rather than a fatal call to a method that was never there.
 */
interface HasUnitCapacity
{
    /** Number of independently bookable units, never less than one. */
    public function unitCapacity(): int;

    /** Bookings attached to this bookable, used to measure occupancy. */
    public function bookings(): Relation;
}
