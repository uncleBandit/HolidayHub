<?php

namespace Database\Factories;

use App\Models\BedAndBreakfast;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BedAndBreakfast>
 */
class BedAndBreakfastFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BedAndBreakfast::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true) . ' BnB';

        return [
            // The provider_id is no longer created here.
            // It is passed from the seeder to the `AccommodationFactory`,
            // which then passes it to this factory via the `has` method.

            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(8),
            'description' => $this->faker->paragraphs(2, true),

            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),

            'rooms' => $this->faker->numberBetween(3, 15),


            'policies' => json_encode([
                'check_in' => '13:00',
                'check_out' => '10:00',
                'cancellation' => 'Free cancellation within 24 hours',
            ]),

            'gallery' => json_encode([
                'https://picsum.photos/1200/800/?random=' . $this->faker->unique()->numberBetween(1001, 2000),
                'https://picsum.photos/1200/800/?random=' . $this->faker->unique()->numberBetween(2001, 3000),
                'https://picsum.photos/1200/800/?random=' . $this->faker->unique()->numberBetween(3001, 4000),
            ]),

            'cover_image' => 'https://picsum.photos/1200/800/?random=' . $this->faker->unique()->numberBetween(1, 1000),
            'price_per_night' => $this->faker->randomFloat(2, 30, 300),
            'avg_rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'reviews_count' => $this->faker->numberBetween(0, 50),
            'provider_id' => Provider::factory(),
            'is_active' => true,
            'is_verified' => true,

            // Remove timestamps and other fields that are automatically handled
            // by Eloquent when using `create`.
        ];
    }
}
