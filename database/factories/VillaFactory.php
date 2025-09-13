<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Villa>
 */
class VillaFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true) . ' Villa';

        // Common amenities
        $amenities = $this->faker->randomElements([
            'WiFi',
            'Private Pool',
            'Air Conditioning',
            'Smart TV',
            'Fully-equipped Kitchen',
            'BBQ Grill',
            'Jacuzzi',
            'Parking',
            'Gym Access',
            'Ocean View',
            'Pet Friendly',
        ], rand(4, 8));

        // Suggested activities (extra spice for next-gen booking systems)
        $activities = $this->faker->randomElements([
            'Snorkeling',
            'Hiking Tours',
            'Wine Tasting',
            'City Excursions',
            'Yoga Retreats',
            'Safari Drives',
            'Boat Trips',
            'Cultural Workshops',
            'Cycling Tours',
            'Spa Treatments',
        ], rand(2, 5));

        return [
            'provider_id' => Provider::factory(),
            'destination_id' => Destination::factory(),

            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'description' => $this->faker->paragraphs(3, true),

            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),

            'bedrooms' => $this->faker->numberBetween(1, 6),
            'bathrooms' => $this->faker->numberBetween(1, 5),
            'max_guests' => $this->faker->numberBetween(2, 12),
            'has_private_pool' => $this->faker->boolean(40),
            'is_featured' => $this->faker->boolean(20),
            'amenities' => $amenities,
            'policies' => json_encode([
                'check_in' => '14:00',
                'check_out' => '11:00',
                'cancellation' => 'Free cancellation within 48 hours',
            ]),
            'gallery' =>json_encode( [
                $this->faker->imageUrl(1200, 800, 'villa', true, 'Villa'),
                $this->faker->imageUrl(1200, 800, 'pool', true, 'Pool'),
                $this->faker->imageUrl(1200, 800, 'interior', true, 'Interior'),
            ]),
            'cover_image' => $this->faker->imageUrl(1200, 800, 'villa', true, 'Cover'),

            'avg_price_per_night' => $this->faker->randomFloat(2, 80, 1500),

            'avg_rating' => $this->faker->randomFloat(1, 3.5, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 120),

            // 👇 Next-gen field for suggested experiences
            'meta_data' => json_encode([
                'activities' => $activities,
                'sustainability' => $this->faker->randomElement(['eco-friendly', 'solar-powered', 'zero-plastic']),
            ]),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
