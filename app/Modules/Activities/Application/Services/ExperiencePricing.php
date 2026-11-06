<?php

namespace App\Modules\Activities\Application\Services;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityOption;
use Carbon\CarbonInterface;

/**
 * What an experience costs, matching what the guest is shown on its page.
 *
 *  - shared (guests join a group): the price is per person, so the total is
 *    the price × the number of people.
 *  - private (the group has it to itself): one price for the whole group, up
 *    to the option's or experience's maximum party size.
 *
 * The price for the day is the host's seasonal rate when one covers the date,
 * otherwise the base price. Experiences are deliberately not priced by the
 * stays' PricingEngine: its nightly rate, flat extra-guest fee, weekend markup
 * and occupancy surge describe a room, not a seat on a boat, and none of them
 * appear on the experience page.
 */
class ExperiencePricing
{
    public function total(Activity|ActivityOption $bookable, CarbonInterface $date, int $participants): float
    {
        $rate = $bookable->getPriceForDate($date->toDateString());

        return round($this->isPrivate($bookable) ? $rate : $rate * max(1, $participants), 2);
    }

    /** The price shown on the page: per person, or per group when private. */
    public function unitLabel(Activity|ActivityOption $bookable): string
    {
        return $this->isPrivate($bookable) ? 'group' : 'person';
    }

    public function isPrivate(Activity|ActivityOption $bookable): bool
    {
        $mode = $bookable->booking_mode
            ?: ($bookable instanceof ActivityOption ? $bookable->activity?->booking_mode : null);

        return $mode === 'private';
    }
}
