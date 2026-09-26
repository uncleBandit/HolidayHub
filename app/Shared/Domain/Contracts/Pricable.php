<?php

namespace App\Shared\Domain\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * What a bookable costs on a given date.
 *
 * Consumed by the Pricing module. Declared separately from Bookable so that
 * pricing obligations are opt-in rather than forced on every implementer, and so
 * that PricingEngine's use of seasonalRates()/offers() is expressed as a
 * requirement instead of an undeclared assumption.
 *
 * Note that PricingEngine additionally calls getExtraGuestFee(), rooms() and
 * bookings() on a Bookable. None of those are declared here, because they only
 * exist on some bookables — that is a genuine design smell in PricingEngine
 * (it assumes a hotel-ish shape) and is left for the Pricing pass rather than
 * papered over by widening this contract.
 */
interface Pricable
{
    /** Recorded seasonal rate overrides. */
    public function seasonalRates(): Relation;

    /** Recorded promotional offers. */
    public function offers(): Relation;

    /** Effective price for a single date, after rates and offers. */
    public function getPriceForDate(string $date): float;
}
