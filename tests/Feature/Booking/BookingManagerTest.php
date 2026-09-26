<?php

use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Booking\Application\Services\BookingManager;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use Carbon\Carbon;

/*
 * The write path for bookings. Every line of BookingManager::create() was
 * broken against the real schema and had never run:
 *
 *   - bookings has no idempotency_key column, so the deduplication lookup threw
 *     (now added by a migration).
 *   - bookings stores the owner as guest_id, not user_id.
 *   - bookable_type stored `get_class($bookable)` instead of the morph alias, so
 *     the row could never be found through the relation again — and threw under
 *     the enforced morph map.
 *   - total_price came from getPriceForDate()/getDefaultMaxGuests() instead of
 *     the canonical PricingEngine, so what was charged disagreed with the quote
 *     shown by previewPrice(). RoomType::getPriceForDate() itself read a
 *     base_price column that does not exist.
 *
 * The bug the user reported was that the preview price and the charged price
 * disagreed; the first two tests pin that they are now the same number.
 */

it('charges the same total the preview quoted', function () {
    $manager = app(BookingManager::class);
    $user = User::factory()->create();
    Guest::factory()->for($user)->create();

    $roomType = RoomType::factory()->create([
        'name' => 'Double Room',
        'slug' => 'double-room-'.uniqid(),
        'price_per_night' => 100.0,
        'capacity' => 2,
        'currency' => 'USD',
    ]);

    $checkIn = Carbon::today()->addDays(5);
    $checkOut = $checkIn->copy()->addDays(3);

    $preview = $manager->previewPrice(
        $roomType->getMorphClass(),
        $roomType->id,
        null,
        $checkIn,
        $checkOut,
        2,
    );

    $booking = $manager->create([
        'bookable_type' => $roomType->getMorphClass(),
        'bookable_id' => $roomType->id,
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkOut->toDateString(),
        'guests' => 2,
    ], $user->id);

    expect($booking->total_amount)->toBe($preview);
    expect($booking->bookable_type)->toBe($roomType->getMorphClass());
    expect($booking->guest_id)->toBe($user->guest->id);
});

it('persists a booking and confirms it through the test gateway', function () {
    $user = User::factory()->create();
    Guest::factory()->for($user)->create();

    $roomType = RoomType::factory()->create([
        'name' => 'Twin Room',
        'slug' => 'twin-room-'.uniqid(),
        'price_per_night' => 90.0,
        'capacity' => 2,
        'currency' => 'USD',
    ]);

    $booking = app(BookingManager::class)->create([
        'bookable_type' => $roomType->getMorphClass(),
        'bookable_id' => $roomType->id,
        'check_in' => Carbon::today()->addDays(5)->toDateString(),
        'check_out' => Carbon::today()->addDays(7)->toDateString(),
        'guests' => 1,
    ], $user->id);

    expect($booking->exists)->toBeTrue();
    expect($booking->status)->toBe('confirmed'); // NullPaymentGateway auto-confirms
    expect($booking->guest_id)->toBe($user->guest->id);
});

it('deduplicates repeated submissions through the idempotency key', function () {
    $manager = app(BookingManager::class);
    $user = User::factory()->create();
    Guest::factory()->for($user)->create();

    $roomType = RoomType::factory()->create([
        'name' => 'Family Room',
        'slug' => 'family-room-'.uniqid(),
        'price_per_night' => 120.0,
        'capacity' => 4,
        'currency' => 'USD',
    ]);

    $payload = [
        'bookable_type' => $roomType->getMorphClass(),
        'bookable_id' => $roomType->id,
        'check_in' => Carbon::today()->addDays(5)->toDateString(),
        'check_out' => Carbon::today()->addDays(8)->toDateString(),
        'guests' => 2,
    ];

    $key = 'booking-'.uniqid();
    $first = $manager->create($payload, $user->id, $key);
    $second = $manager->create($payload, $user->id, $key);

    expect($second->id)->toBe($first->id);
    expect(Booking::where('idempotency_key', $key)->count())->toBe(1);
});
