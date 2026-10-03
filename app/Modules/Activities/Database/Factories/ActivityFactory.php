<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(3);

        return [
            'provider_id' => Provider::factory(),
            'destination_id' => Destination::factory(),
            'category_id' => ActivityCategory::factory(),
            'name' => $name,
            'type' => $this->faker->randomElement(['Adventure', 'Cultural', 'Relaxation', 'Family', 'Wellness']),
            'description' => $this->faker->paragraph(4),
            'short_description' => $this->faker->sentence(),
            'thumbnail' => $this->faker->imageUrl(400, 300, 'tourism', true, 'activity'),
            'meta_title' => $this->faker->sentence(6),
            'meta_description' => $this->faker->paragraph(2),
            'tags' => $this->faker->randomElements(['family-friendly', 'adventure', 'romantic', 'group', 'spa', 'eco'], rand(2, 4)),
            'base_price' => $this->faker->randomFloat(2, 20, 500),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'KES']),
            'duration_minutes' => $this->faker->numberBetween(30, 480),
            'capacity' => $this->faker->numberBetween(5, 50),
            'min_age' => $this->faker->numberBetween(0, 12),
            'max_age' => $this->faker->numberBetween(18, 99),
            'timezone' => 'Africa/Nairobi',
            'booking_mode' => 'shared',
            'included_participants' => 1,
            'minimum_notice_minutes' => 0,
            'weather_dependent' => false,
            'is_featured' => $this->faker->boolean(25),
            'rating' => $this->faker->randomFloat(2, 3, 5),
            'reviews_count' => $this->faker->numberBetween(0, 200),
            'bookings_count' => $this->faker->numberBetween(0, 500),
        ];
    }

    public function published(): static
    {
        return $this->afterCreating(function (Activity $activity): void {
            $activity->forceFill([
                'status' => ActivityStatus::Published,
                'verification_status' => ActivityVerificationStatus::Approved,
                'is_active' => true,
                'published_at' => now(),
                'verified_at' => now(),
            ])->save();
        });
    }

    /**
     * State for premium activities (high price, top rating, featured).
     */
    public function premium(): static
    {
        return $this->state(fn () => [
            'base_price' => $this->faker->randomFloat(2, 300, 1000),
            'rating' => 5.0,
            'is_featured' => true,
        ]);
    }

    /**
     * State for free or demo activities.
     */
    public function free(): static
    {
        return $this->state(fn () => [
            'base_price' => 0.0,
        ]);
    }
}
