<?php

namespace App\Shared\Domain\Contracts;

use Carbon\Carbon;

/**
 * The effective price of one night of a bookable.
 *
 * Every caller that needs a number — the booking calendar, the booking flow, a
 * quote — should come through this rather than re-deriving one. The calendar
 * used to run its own lookup against the seasonal-rate and offer tables and
 * quietly get it wrong (it matched on a `date_key` column that does not exist),
 * which is how the same bookable ended up showing a base price on the calendar
 * and a different total at checkout.
 *
 * Declared here, in the shared kernel, so that consumers such as the
 * Availability module's calendar can ask for a price without importing the
 * Pricing module. PricingEngine is bound to this contract.
 */
interface NightlyPricer
{
    public function nightlyPrice(Bookable&Pricable $bookable, Carbon $date, int $guests = 1): float;
}
