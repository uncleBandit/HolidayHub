<?php

namespace App\Shared\Domain\Contracts;

/**
 * What a thing IS, in the terms booking needs in order to reference it.
 *
 * This is deliberately the narrowest of the three bookable contracts. It used
 * to also demand:
 *
 *     public function availabilities(): Relation;   // Availability module
 *     public function seasonalRates(): Relation;     // Pricing module
 *     public function offers(): Relation;            // Catalog module
 *
 * Those three methods are the reason this interface was a problem rather than a
 * solution. Every implementer was forced to expose relations owned by three
 * unrelated modules purely to satisfy it, so each of the seven bookable models
 * carried imports of Availability, SeasonalRate and Offer that had nothing to do
 * with what that model *is*. Accommodation ended up with 42 cross-module imports
 * in its domain layer, a large share of them traceable to this one interface.
 *
 * The obligations have been split by concern instead:
 *
 *   - Bookable          what it is            (Accommodation, Activities, Packages)
 *   - AvailabilityAware whether it is free    (consumed by Availability)
 *   - Pricable          what it costs          (consumed by Pricing)
 *
 * A model implements only the capabilities it genuinely has. Adding a method to
 * this interface still breaks every implementer, so keep it that way: if a
 * consumer needs more, it should say so through its own contract rather than
 * widening the one everybody is forced to implement.
 *
 * @see AvailabilityAware
 * @see Pricable
 */
interface Bookable
{
    /** Stable identifier used in booking references. */
    public function getId(): int;

    /** Short discriminator, e.g. 'hotel', 'villa', 'package'. */
    public function getType(): string;

    public function getName(): string;

    public function getDescription(): string;

    /** @return array<int, string> */
    public function getImages(): array;

    /** Undiscounted nightly rate before seasonal rates and offers. */
    public function getBasePrice(): float;

    /** How many guests the base price already covers. */
    public function getIncludedGuests(): int;

    /** ISO currency code the base price is expressed in. */
    public function getCurrency(): string;

    public function getDefaultMaxGuests(): int;
}
