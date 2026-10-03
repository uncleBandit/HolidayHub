<?php

use App\Modules\Activities\Application\Services\ActivityPublicationService;
use App\Modules\Activities\Application\Services\ActivityScheduleGenerator;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySchedule;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

it('requires complete activity details and records publication transitions', function () {
    $activity = Activity::factory()->create();
    $publication = app(ActivityPublicationService::class);

    expect(fn () => $publication->submit($activity))
        ->toThrow(ValidationException::class);

    $activity->schedules()->create([
        'day_of_week' => now()->dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '12:00',
        'timezone' => 'Africa/Nairobi',
        'capacity' => 10,
        'is_active' => true,
    ]);

    $submitted = $publication->submit($activity);
    expect($submitted->status)->toBe(ActivityStatus::Submitted)
        ->and($submitted->verification_status)->toBe(ActivityVerificationStatus::Pending);

    $reviewer = \App\Modules\Identity\Domain\Models\User::factory()->create();
    $approved = $publication->approve($submitted, $reviewer->id);
    $published = $publication->publish($approved);

    expect($published->isPublished())->toBeTrue()
        ->and($published->verificationHistory()->count())->toBe(2);

    $published->update(['description' => str_repeat('Updated activity description. ', 3)]);

    expect($published->fresh()->status)->toBe(ActivityStatus::Draft)
        ->and($published->fresh()->verificationHistory()->count())->toBe(3);
});

it('generates recurring sessions in the activity timezone without duplicating sessions', function () {
    $activity = Activity::factory()->create();
    $start = now()->addDay()->startOfDay();
    $weekday = $start->dayOfWeek;
    $schedule = ActivitySchedule::factory()->create([
        'activity_id' => $activity->id,
        'day_of_week' => $weekday,
        'start_time' => '08:30',
        'end_time' => '10:30',
        'timezone' => 'Africa/Nairobi',
        'active_from' => $start->toDateString(),
        'active_until' => $start->copy()->addDays(14)->toDateString(),
    ]);

    $generator = app(ActivityScheduleGenerator::class);
    $first = $generator->generate($schedule, $start, $start->copy()->addDays(14));
    $second = $generator->generate($schedule, $start, $start->copy()->addDays(14));

    expect($first)->not->toBeEmpty()
        ->and($second)->toHaveCount($first->count())
        ->and($schedule->sessions()->count())->toBe($first->count())
        ->and($first->first()->starts_at->setTimezone('Africa/Nairobi')->format('H:i'))->toBe('08:30');
});

it('reserves session capacity under a booking and handles idempotent retries', function () {
    $activity = Activity::factory()->published()->create(['capacity' => 4]);
    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 3,
        'starts_at' => now()->addDays(7)->setTime(9, 0),
        'ends_at' => now()->addDays(7)->setTime(12, 0),
    ]);
    $user = \App\Modules\Identity\Domain\Models\User::factory()->create();
    \App\Modules\Identity\Domain\Models\Guest::factory()->for($user)->create();
    $bookingService = app(\App\Modules\Activities\Application\Services\ActivitySessionBookingService::class);

    $booking = $bookingService->book($activity, $session, $user, 2, 'activity-booking-once');
    $retry = $bookingService->book($activity, $session, $user, 2, 'activity-booking-once');

    expect($retry->id)->toBe($booking->id)
        ->and($session->fresh()->booked_capacity)->toBe(2)
        ->and($booking->activity_session_id)->toBe($session->id)
        ->and($booking->bookable_type)->toBe('activity');

    expect(fn () => $bookingService->book($activity, $session, $user, 2, 'activity-booking-twice'))
        ->toThrow(ValidationException::class);
    expect($session->fresh()->booked_capacity)->toBe(2);
});

it('enforces minimum notice and session booking cutoffs before reserving places', function () {
    $activity = Activity::factory()->published()->create([
        'minimum_notice_minutes' => 120,
    ]);
    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'capacity' => 3,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHours(2),
        'booking_cutoff_at' => now()->subMinute(),
    ]);
    $user = User::factory()->create();
    Guest::factory()->for($user)->create();
    $bookingService = app(\App\Modules\Activities\Application\Services\ActivitySessionBookingService::class);

    expect($activity->isAvailable(
        $session->starts_at->startOfDay()->toDateTimeString(),
        $session->starts_at->startOfDay()->addDay()->toDateTimeString()
    ))->toBeFalse();

    expect(fn () => $bookingService->book($activity, $session, $user, 1))
        ->toThrow(ValidationException::class);
    expect($session->fresh()->booked_capacity)->toBe(0);

    $session->update([
        'booking_cutoff_at' => null,
        'starts_at' => now()->addMinutes(60),
        'ends_at' => now()->addMinutes(180),
    ]);
    expect(fn () => $bookingService->book($activity, $session, $user, 1))
        ->toThrow(ValidationException::class);
    expect($session->fresh()->booked_capacity)->toBe(0);
});

it('accepts reviews only for completed activity bookings and prevents duplicate reviews', function () {
    $activity = Activity::factory()->published()->create();
    $session = ActivitySession::factory()->create([
        'activity_id' => $activity->id,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDays(2)->addHours(2),
    ]);
    $user = User::factory()->create();
    $guest = Guest::factory()->for($user)->create();
    $booking = Booking::factory()->completed()->create([
        'guest_id' => $guest->id,
        'bookable_type' => 'activity',
        'bookable_id' => $activity->id,
        'activity_session_id' => $session->id,
        'check_in_date' => now()->subDays(2),
        'check_out_date' => now()->subDay(),
    ]);

    $payload = [
        'booking_id' => $booking->id,
        'rating' => 5,
        'title' => 'Wonderful experience',
        'comment' => 'The guide was knowledgeable and the activity was excellent.',
    ];

    $this->actingAs($user)
        ->postJson('/api/v1/activities/'.$activity->id.'/reviews', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $this->actingAs($user)
        ->postJson('/api/v1/activities/'.$activity->id.'/reviews', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('booking_id');
});

it('creates provider-owned drafts without allowing provider id spoofing', function () {
    Role::findOrCreate('provider', 'web');
    $user = User::factory()->create();
    $user->assignRole('provider');
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'active' => true,
    ]);
    $category = \App\Modules\Activities\Domain\Models\ActivityCategory::factory()->create();
    $destination = Destination::factory()->create();
    $payload = [
        'destination_id' => $destination->id,
        'category_id' => $category->id,
        'name' => 'Community pottery workshop',
        'description' => 'Learn traditional pottery techniques from a local artisan.',
        'currency' => 'KES',
        'duration_minutes' => 120,
        'timezone' => 'Africa/Nairobi',
    ];

    $this->actingAs($user)
        ->postJson('/api/v1/activities', [...$payload, 'provider_id' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('provider_id');

    $this->actingAs($user)
        ->postJson('/api/v1/activities', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', ActivityStatus::Draft->value);

    expect(Activity::query()->where('name', $payload['name'])->value('provider_id'))
        ->toBe($provider->id);
});

it('serves only published activities through public discovery and filters them safely', function () {
    $published = Activity::factory()->published()->create(['name' => 'Nairobi Cooking Class']);
    Activity::factory()->create(['name' => 'Draft Cooking Class']);

    $response = $this->getJson('/api/v1/activities?search=cooking');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $published->id)
        ->assertJsonPath('data.0.status', ActivityStatus::Published->value);

    $this->getJson('/api/v1/activities/'.$published->id)->assertOk();
});
