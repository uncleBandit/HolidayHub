<?php

use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Availability\Application\Services\AvailabilityEngine;
use App\Modules\Availability\Domain\Models\Availability;
use App\Modules\Booking\Domain\Models\Booking;
use Carbon\Carbon;

/*
 * AvailabilityEngine answers "can this be booked, and what have we recorded".
 *
 * The previous implementation was written against a per-date schema that does
 * not exist. The `availabilities` table stores ranges — start_date, end_date,
 * quantity, price_per_night, status — while the code queried a `date` column and
 * read is_available / price / min_stay / max_guests. Booking overlap checks
 * queried `check_in` / `check_out`, which bookings does not have either (the
 * columns are check_in_date / check_out_date). Every one of those queries threw
 * or silently returned nothing.
 *
 * These tests pin the real schema, including a blocked row and a confirmed
 * booking influencing the answer.
 */

function aRoomTypeForAvailability(): RoomType
{
    return RoomType::factory()->create([
        'name' => 'Deluxe Room',
        'slug' => 'deluxe-room-'.uniqid(),
        'price_per_night' => 100.0,
        'capacity' => 2,
        'currency' => 'USD',
    ]);
}

function anAvailabilityRange(RoomType $roomType, Carbon $start, Carbon $end, array $overrides = []): Availability
{
    return $roomType->availabilities()->create(array_merge([
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'quantity' => 1,
        'price_per_night' => 120.0,
        'status' => 'available',
    ], $overrides));
}

function aBlockingBooking(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): Booking
{
    return Booking::create([
        'guest_id' => App\Modules\Identity\Domain\Models\Guest::factory()
            ->for(App\Modules\Identity\Domain\Models\User::factory()->create())
            ->create()->id,
        'bookable_type' => $roomType->getMorphClass(),
        'bookable_id' => $roomType->id,
        'check_in_date' => $checkIn,
        'check_out_date' => $checkOut,
        'guests_adults' => 1,
        'total_amount' => 100.0,
        'currency' => 'USD',
        'status' => 'confirmed',
        'confirmation_code' => strtoupper(uniqid()),
    ]);
}

it('expands an availability range across its dates and leaves others unconfigured', function () {
    $roomType = aRoomTypeForAvailability();
    $start = Carbon::today()->addDays(10);
    $end = $start->copy()->addDays(2);

    anAvailabilityRange($roomType, $start, $end);

    $days = app(AvailabilityEngine::class)->forBookable($roomType, $start, $start->copy()->addDays(3));

    expect($days[$start->toDateString()])
        ->available->toBeTrue()
        ->price->toBe(120.0)
        ->source->toBe('database');

    // The day after the range has no record at all.
    expect($days[$start->copy()->addDays(3)->toDateString()])
        ->available->toBeTrue()
        ->price->toBeNull()
        ->source->toBe('unconfigured');
});

it('marks a blocked availability row as unavailable', function () {
    $roomType = aRoomTypeForAvailability();
    $date = Carbon::today()->addDays(10);

    anAvailabilityRange($roomType, $date, $date->copy()->addDays(2), ['status' => 'maintenance']);

    $days = app(AvailabilityEngine::class)->forBookable($roomType, $date, $date->copy()->addDays(2));

    expect($days[$date->toDateString()]['available'])->toBeFalse();
});

it('reports a bookable with no availability rows as unconfigured but open', function () {
    $roomType = aRoomTypeForAvailability();
    $date = Carbon::today()->addDays(10);

    $days = app(AvailabilityEngine::class)->forBookable($roomType, $date, $date);

    expect($days[$date->toDateString()])
        ->available->toBeTrue()
        ->price->toBeNull()
        ->source->toBe('unconfigured');
});

it('knows a confirmed booking blocks overlapping dates but not later ones', function () {
    $roomType = aRoomTypeForAvailability();
    $engine = app(AvailabilityEngine::class);
    $checkIn = Carbon::today()->addDays(10);
    $checkOut = $checkIn->copy()->addDays(3);

    aBlockingBooking($roomType, $checkIn, $checkOut);

    expect($engine->isAvailable($roomType->id, $roomType->getMorphClass(), $checkIn, $checkOut))->toBeFalse();
    expect($engine->isAvailable($roomType->id, $roomType->getMorphClass(), $checkOut->copy()->addDays(1), $checkOut->copy()->addDays(4)))->toBeTrue();
});

it('counts a blocking booking towards capacity and blocks a fully booked day', function () {
    $roomType = aRoomTypeForAvailability();
    $date = Carbon::today()->addDays(10);

    // One unit, and one unit is booked.
    anAvailabilityRange($roomType, $date, $date->copy()->addDays(2));
    aBlockingBooking($roomType, $date, $date->copy()->addDays(2));

    $days = app(AvailabilityEngine::class)->forBookable($roomType, $date, $date->copy()->addDays(2));

    expect($days[$date->toDateString()])
        ->available->toBeFalse()
        ->booked->toBe(1)
        ->capacity->toBe(1);
});
