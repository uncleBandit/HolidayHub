<?php

namespace App\Modules\Pricing\Application\Services;

use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\HasUnitCapacity;
use App\Shared\Domain\Contracts\NightlyPricer;
use App\Shared\Domain\Contracts\Pricable;
use Carbon\Carbon;

/**
 * The single source of truth for what a bookable costs.
 *
 * This used to be one of three competing implementations, none of which agreed:
 *
 *   - PricingEngine        (this class)      per-date, the only one modelling
 *                                             seasonal rates, weekends,
 *                                             occupancy and offers
 *   - PricingContext       via BookingManager::previewPrice()
 *   - the booking calendar hand-rolled its own lookup and always fell back to
 *     the base price
 *
 * It was also not merely inconsistent but unusable: calculateDaily() called
 * getHolidaySurcharge(), a method that had been commented out, so it threw for
 * every input and nothing had caught it because there were no tests. Alongside
 * that it read a `price` column that seasonal_rates does not have (it is
 * `rate`), called a getExtraGuestFee() that no model defined, and called
 * rooms() on bookables that have no such relation.
 *
 * All three are fixed here. Bookings occupy a unit via the HasUnitCapacity
 * contract, so a bookable with no unit concept simply skips demand pricing
 * rather than crashing.
 *
 * The strategy classes and PricingContext are now unreferenced; see
 * BedAndBreakfastPricingStrategy, which now delegates here.
 */
class PricingEngine implements NightlyPricer
{
    /** Booking statuses that occupy inventory. */
    private const BLOCKING_STATUSES = ['confirmed', 'checked_in'];

    /**
     * Total price for a date range.
     */
    public function calculateTotal(Bookable&Pricable $bookable, Carbon $checkIn, Carbon $checkOut, int $guests = 1): float
    {
        $nights = max(1, (int) $checkIn->diffInDays($checkOut));

        $total = 0.0;
        for ($night = 0; $night < $nights; $night++) {
            $total += $this->calculateDaily($bookable, $checkIn->copy()->addDays($night), $guests);
        }

        return round($total, 2);
    }

    /**
     * Effective price for a single night.
     *
     * Order matters: a seasonal rate replaces the base rate rather than
     * adjusting it, surcharges are applied to the resulting rate, and a
     * promotional discount comes off the total.
     */
    public function calculateDaily(Bookable&Pricable $bookable, Carbon $date, int $guests = 1): float
    {
        $price = $this->seasonalRateFor($bookable, $date) ?? $bookable->getBasePrice();

        if ($date->isWeekend()) {
            $price *= $this->config('weekend_multiplier', 1.10);
        }

        $price += $this->extraGuestCharge($bookable, $guests);

        if ($this->occupancyRate($bookable, $date) > $this->config('high_occupancy_threshold', 0.8)) {
            $price *= $this->config('high_occupancy_multiplier', 1.15);
        }

        if ($promo = $this->activeOfferFor($bookable, $date)) {
            $price -= $price * ($promo->discount_percent / 100);
        }

        return round($price, 2);
    }

    /**
     * NightlyPricer implementation, so consumers can depend on the contract
     * instead of on this class.
     */
    public function nightlyPrice(Bookable&Pricable $bookable, Carbon $date, int $guests = 1): float
    {
        return $this->calculateDaily($bookable, $date, $guests);
    }

    /**
     * The seasonal rate covering a date, if any.
     *
     * The column is `rate`. The previous implementation read `->price`, which is
     * not a column on seasonal_rates, so a covered date resolved to null and the
     * nightly price collapsed to zero.
     */
    protected function seasonalRateFor(Bookable&Pricable $bookable, Carbon $date): ?float
    {
        $rate = $bookable->seasonalRates()
            ->where('active', true)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            // Most specific window wins when several overlap.
            ->orderByDesc('start_date')
            ->first();

        return $rate?->rate === null ? null : (float) $rate->rate;
    }

    /**
     * Per-night charge for guests beyond the included count.
     */
    protected function extraGuestCharge(Bookable&Pricable $bookable, int $guests): float
    {
        $extraGuests = max(0, $guests - $bookable->getIncludedGuests());

        return $extraGuests * $this->config('extra_guest_fee', 20.0);
    }

    /**
     * The active offer covering a date, if any.
     *
     * The column is `discount_percent`.
     */
    protected function activeOfferFor(Bookable&Pricable $bookable, Carbon $date): ?object
    {
        return $bookable->offers()
            ->where('active', true)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Fraction of the available units booked on a date.
     *
     * Returns 0.0 for bookables with no unit concept, so demand pricing simply
     * does not apply to them instead of throwing on a missing relation.
     */
    protected function occupancyRate(Bookable&Pricable $bookable, Carbon $date): float
    {
        if (! $bookable instanceof HasUnitCapacity) {
            return 0.0;
        }

        $capacity = max(1, $bookable->unitCapacity());

        $booked = $bookable->bookings()
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '>', $date)
            ->count();

        return min($booked / $capacity, 1.0);
    }

    /**
     * Read a tunable from the module's config, with a fallback.
     *
     * The fallback matters: mergeConfigFrom only runs when the Pricing module is
     * enabled, so a hard-coded default keeps this class usable if it is ever
     * resolved outside a booted module registry.
     */
    protected function config(string $key, float $default): float
    {
        $value = config('pricing.engine.'.$key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }
}
