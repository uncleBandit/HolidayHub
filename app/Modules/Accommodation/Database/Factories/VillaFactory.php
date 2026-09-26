<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Accommodation\Domain\Models\Villa>
 */
class VillaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Villa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true).' Villa';

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
            // The provider_id is no longer created here.
            // It is passed from the seeder to the `AccommodationFactory`,
            // which then passes it to this factory via the `has` method.

            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
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
            'policies' => json_encode([
                'check_in' => '14:00',
                'check_out' => '11:00',
                'cancellation' => 'Free cancellation within 48 hours',
            ]),
            'gallery' => json_encode([
                'https://picsum.photos/1200/800/?random='.$this->faker->unique()->numberBetween(1001, 2000),
                'https://picsum.photos/1200/800/?random='.$this->faker->unique()->numberBetween(2001, 3000),
                'https://picsum.photos/1200/800/?random='.$this->faker->unique()->numberBetween(3001, 4000),
            ]),
            'cover_image' => 'https://picsum.photos/1200/800/?random='.$this->faker->unique()->numberBetween(1, 1000),

            'avg_price_per_night' => $this->faker->randomFloat(2, 80, 1500),

            'avg_rating' => $this->faker->randomFloat(1, 3.5, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 120),
            'provider_id' => Provider::factory(),

            'meta_data' => json_encode([
                'activities' => $activities,
                'sustainability' => $this->faker->randomElement(['eco-friendly', 'solar-powered', 'zero-plastic']),
            ]),
        ];
    }
}
