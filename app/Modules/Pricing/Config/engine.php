<?php

/*
 * Tunables for PricingEngine, the single calculator for what a bookable costs.
 *
 * Reachable as config('pricing.engine.*') — ModulesServiceProvider merges every
 * module's Config/<file>.php under config('<module id>.<file>').
 *
 * The per-guest surcharge in particular used to be a literal 20 in two different
 * places, and a call to a getExtraGuestFee() method that no model defined.
 */

return [

    /*
     * Multiplier applied to the nightly rate on Saturday and Sunday.
     */
    'weekend_multiplier' => 1.10,

    /*
     * Charged per guest per night, for each guest beyond the number the
     * bookable's base price already includes.
     */
    'extra_guest_fee' => 20.0,

    /*
     * Demand pricing: once more than this fraction of the available units is
     * booked, the nightly rate is multiplied by the factor below. Only applies to
     * bookables that report a unit capacity.
     */
    'high_occupancy_threshold' => 0.8,
    'high_occupancy_multiplier' => 1.15,

];
