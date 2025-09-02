<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $startDate = $this->faker->dateTimeBetween('now', '+3 months');
        $endDate = (clone $startDate)->modify('+'.rand(1, 8).' hours');

        // Generate gallery images
        $gallery = [
            "https://source.unsplash.com/800x600/?activity," . $this->faker->word(),
            "https://source.unsplash.com/800x600/?tour," . $this->faker->word(),
        ];

        $basePrice = $this->faker->randomFloat(2, 20, 500);
        $currency = $this->faker->randomElement(['USD', 'EUR', 'GBP', 'KES']);

        return [
            'destination_id' => Destination::factory(),
            'hotel_id' => $this->faker->boolean(50) ? Hotel::factory() : null, // 50% chance linked to hotel
            'name' => $this->faker->sentence(3),
            'slug' => Str::slug($this->faker->unique()->sentence(3)),
            'type' => $this->faker->randomElement(['Adventure', 'Cultural', 'Relaxation', 'Family', 'Wellness']),
            'description' => $this->faker->paragraph(4),
            'thumbnail' => $this->faker->imageUrl(400, 300, 'tourism', true, 'activity'),
            'gallery' => json_encode($gallery),
            'video_url' => $this->faker->optional(0.3)->url(),
            'meta_title' => $this->faker->sentence(6),
            'meta_description' => $this->faker->paragraph(2),
            'tags' => json_encode($this->faker->randomElements(['family-friendly', 'adventure', 'romantic', 'group', 'spa', 'eco'], rand(2, 4))),
            'base_price' => $basePrice,
            'currency' => $currency,
            'duration_minutes' => rand(30, 480),
            'capacity' => rand(5, 50),
            'min_age' => rand(0, 12),
            'max_age' => rand(12, 99),
            'is_featured' => $this->faker->boolean(25), // 25% chance featured
            'is_active' => $this->faker->boolean(90),
            'available_from' => $startDate,
            'available_to' => $endDate,
            'rating' => $this->faker->randomFloat(2, 3, 5),
            'reviews_count' => $this->faker->numberBetween(0, 200),
            'bookings_count' => $this->faker->numberBetween(0, 500),
        ];
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
            'is_active' => true,
        ]);
    }

    /**
     * State for free or demo activities.
     */
    public function free(): static
    {
        return $this->state(fn () => [
            'base_price' => 0.0,
            'is_active' => true,
        ]);
    }
}
