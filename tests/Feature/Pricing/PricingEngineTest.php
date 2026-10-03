<?php

use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Pricing\Application\Services\PricingEngine;
use App\Modules\Pricing\Domain\Models\SeasonalRate;
use Carbon\Carbon;

/*
 * PricingEngine is the single source of truth for what a bookable costs.
 *
 * Every test here pins a specific defect found by checking the code against a
 * real migrated schema rather than against intent:
 *
 *   - `seasonal_rates` has a `rate` column, not `price`. Reading `->price`
 *     returned null, so any date covered by a seasonal rate was priced at 0.
 *   - `getExtraGuestFee()` was called but defined on no model at all, so any
 *     booking with more guests than the base price covered threw.
 *   - `$bookable->rooms()` exists only on Hotel and RoomType, so occupancy
 *     pricing threw for every other bookable.
 *
 * Assertions use mid-week dates so the weekend surcharge stays out of the way,
 * and a RoomType with no rooms so occupancy stays at zero.
 */

/** A weekday far enough out that no fixture overlaps it. */
function midWeek(int $offsetDays = 3): Carbon
{
    $date = Carbon::today()->addDays($offsetDays);

    while ($date->isWeekend()) {
        $date->addDay();
    }

    return $date;
}

function aRoomType(array $attributes = []): RoomType
{
    return RoomType::factory()->create(array_merge([
        'price_per_night' => 100.0,
        'capacity' => 2,
        'currency' => 'USD',
    ], $attributes));
}

function aSeasonalRateFor(RoomType $roomType, Carbon $date, float $rate): SeasonalRate
{
    return SeasonalRate::create([
        'seasonal_rateable_type' => $roomType->getMorphClass(),
        'seasonal_rateable_id' => $roomType->id,
        'rate' => $rate,
        'currency' => 'USD',
        'start_date' => $date->copy()->startOfDay(),
        'end_date' => $date->copy()->endOfDay(),
        'active' => true,
    ]);
}

function anOfferFor(RoomType $roomType, Carbon $date, int $discountPercent): Offer
{
    return Offer::create([
        'title' => 'Midweek saver',
        'slug' => 'midweek-saver-'.uniqid(),
        'price' => 100.0,
        'discount_percent' => $discountPercent,
        'offerable_type' => $roomType->getMorphClass(),
        'offerable_id' => $roomType->id,
        'start_date' => $date->copy()->startOfDay(),
        'end_date' => $date->copy()->endOfDay(),
        'active' => true,
    ]);
}

it('charges the base price when nothing overrides it', function () {
    $roomType = aRoomType();

    expect(app(PricingEngine::class)->calculateDaily($roomType, midWeek()))->toBe(100.0);
});

it('applies a seasonal rate instead of collapsing the price to zero', function () {
    $roomType = aRoomType();
    $date = midWeek();

    aSeasonalRateFor($roomType, $date, 150.0);

    expect(app(PricingEngine::class)->calculateDaily($roomType, $date))->toBe(150.0);
});

it('ignores an inactive seasonal rate', function () {
    $roomType = aRoomType();
    $date = midWeek();

    aSeasonalRateFor($roomType, $date, 150.0)->update(['active' => false]);

    expect(app(PricingEngine::class)->calculateDaily($roomType, $date))->toBe(100.0);
});

it('applies an active offer discount', function () {
    $roomType = aRoomType();
    $date = midWeek();

    anOfferFor($roomType, $date, 10);

    expect(app(PricingEngine::class)->calculateDaily($roomType, $date))->toBe(90.0);
});

it('ignores an inactive offer', function () {
    $roomType = aRoomType();
    $date = midWeek();

    anOfferFor($roomType, $date, 10)->update(['active' => false]);

    expect(app(PricingEngine::class)->calculateDaily($roomType, $date))->toBe(100.0);
});

it('adds a surcharge for guests beyond the included count without crashing', function () {
    $engine = app(PricingEngine::class);
    $roomType = aRoomType(['capacity' => 2]);
    $date = midWeek();

    $included = $engine->calculateDaily($roomType, $date, 1);
    $withExtras = $engine->calculateDaily($roomType, $date, 4);

    expect($withExtras)->toBeGreaterThan($included);
});

it('prices a bookable that has no rooms relation without crashing', function () {
    $engine = app(PricingEngine::class);
    $activity = Activity::factory()->create(['base_price' => 80.0, 'included_participants' => 1]);

    expect(fn () => $engine->calculateDaily($activity, midWeek()))
        ->not->toThrow(BadMethodCallException::class);

    expect($engine->calculateDaily($activity, midWeek()))->toBeGreaterThan(0.0);
});

it('totals a date range as the sum of its nightly rates', function () {
    $engine = app(PricingEngine::class);
    $roomType = aRoomType();
    $checkIn = midWeek();
    $nights = 3;

    // Built from the engine's own daily figures rather than a hard-coded total,
    // so a weekend night inside the range does not make this assertion lie.
    $expected = 0.0;
    for ($night = 0; $night < $nights; $night++) {
        $date = $checkIn->copy()->addDays($night);
        aSeasonalRateFor($roomType, $date, 150.0);
        $expected += $engine->calculateDaily($roomType, $date);
    }

    $total = $engine->calculateTotal($roomType, $checkIn, $checkIn->copy()->addDays($nights), 1);

    expect($total)->toBe(round($expected, 2));
});
