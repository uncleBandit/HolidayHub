<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Accommodation\Domain\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Hotel::class;

    /**
     * Define the model's default state.
     *
     * The HotelFactory should no longer know about a Destination.
     * The destination relationship is handled by the AccommodationFactory.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = $this->faker->numberBetween(80, 500);
        $discount = $this->faker->boolean(70) ? $this->faker->numberBetween(5, 40) : 0;
        $finalPrice = $originalPrice - ($originalPrice * $discount / 100);

        return [
            'name' => $this->faker->company().' Hotel',
            'slug' => Str::slug($this->faker->unique()->company().'-hotel'),
            'description' => $this->faker->paragraph(5),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'stars' => $this->faker->numberBetween(1, 5),
            'is_featured' => $this->faker->boolean(30),
            'policies' => json_encode([
                'check_in' => $this->faker->time('H:i'),
                'check_out' => $this->faker->time('H:i'),
                'cancellation' => 'Flexible',
            ]),
            'cover_image' => 'https://picsum.photos/1200/800/?random='.$this->faker->unique()->numberBetween(1, 1000),
            'avg_price_per_night' => round($finalPrice, 2),
            'avg_rating' => $this->faker->randomFloat(1, 2.5, 5.0),
            'reviews_count' => $this->faker->numberBetween(10, 2000),
            'provider_id' => Provider::factory(), // A default value if not provided

            // The destination_id is no longer needed on the Hotel model.
            // This is now on the `accommodations` table.
        ];
    }
}
