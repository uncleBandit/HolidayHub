<?php

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\RoomStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\AccommodationVerification;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Accommodation\Domain\Models\RoomPrice;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Validation\ValidationException;

it('creates valid accommodation and verification factory records', function () {
    $accommodation = Accommodation::factory()->create();
    $verification = AccommodationVerification::factory()->create();
    $room = Room::factory()->create();

    expect($accommodation->bookable)->toBeInstanceOf(Hotel::class)
        ->and($accommodation->provider_id)->toBe($accommodation->bookable->provider_id)
        ->and($verification->accommodation)->toBeInstanceOf(Accommodation::class)
        ->and($room->roomType->hotel_id)->toBe($room->hotel_id)
        ->and($room->is_available)->toBeTrue();

    $room->update(['status' => RoomStatus::OutOfService]);

    expect($room->fresh()->status)->toBe(RoomStatus::OutOfService)
        ->and($room->fresh()->is_available)->toBeFalse();
});

it('creates and synchronizes the canonical accommodation record for a property', function () {
    $hotel = Hotel::factory()->create();
    $accommodation = $hotel->accommodation()->firstOrFail();

    expect($accommodation->bookable_type)->toBe($hotel->getMorphClass())
        ->and($accommodation->bookable_id)->toBe($hotel->id)
        ->and($accommodation->provider_id)->toBe($hotel->provider_id)
        ->and($accommodation->status)->toBe(AccommodationStatus::Draft)
        ->and($accommodation->verification_status)->toBe(VerificationStatus::Unverified);

    $hotel->update(['name' => 'Updated Hotel Name']);

    expect($hotel->accommodation()->firstOrFail()->name)->toBe('Updated Hotel Name');
});

it('requires complete listing data before review and supports approval with audit history', function () {
    $hotel = Hotel::factory()->create();
    $accommodation = $hotel->accommodation()->firstOrFail();
    $destination = Destination::factory()->create();
    $accommodation->update(['destination_id' => $destination->id]);

    expect(fn () => app(AccommodationPublicationService::class)->submitForReview($accommodation))
        ->toThrow(ValidationException::class);

    $hotel->update(['cover_image' => 'hotels/cover.jpg']);
    $hotel->amenities()->attach(Amenity::create([
        'name' => 'Wi-Fi',
        'slug' => 'wifi-test',
        'type' => 'hotel',
        'active' => true,
    ])->id);
    RoomType::factory()->create([
        'hotel_id' => $hotel->id,
        'price_per_night' => 125,
    ]);

    $publicationService = app(AccommodationPublicationService::class);
    $pending = $publicationService->submitForReview($accommodation);

    expect($pending->status)->toBe(AccommodationStatus::PendingReview)
        ->and($pending->verification_status)->toBe(VerificationStatus::Pending);

    $reviewer = User::factory()->create();
    $published = $publicationService->approve($pending, $reviewer->id);

    expect($published->isPublished())->toBeTrue()
        ->and($published->verificationHistory()->count())->toBe(2)
        ->and($hotel->fresh()->is_verified)->toBeTrue();
});

it('uses the effective room price for dates in and outside an override period', function () {
    $hotel = Hotel::factory()->create();
    $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id, 'price_per_night' => 100]);
    $room = Room::factory()->create([
        'hotel_id' => $hotel->id,
        'room_type_id' => $roomType->id,
        'room_number' => 101,
    ]);

    RoomPrice::create([
        'room_id' => $room->id,
        'base_price' => 150,
        'discount_price' => 120,
        'start_date' => '2030-06-01',
        'end_date' => '2030-06-10',
        'meta' => ['source' => 'seasonal'],
    ]);

    expect($roomType->getPriceForDate('2030-06-05'))->toBe(120.0)
        ->and($roomType->getPriceForDate('2030-06-11'))->toBe(100.0);
});
