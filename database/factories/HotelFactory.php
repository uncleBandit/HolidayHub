<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Destination;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = $this->faker->numberBetween(80, 500);
        $discount = $this->faker->boolean(70) ? $this->faker->numberBetween(5, 40) : 0; // 70% chance
        $finalPrice = $originalPrice - ($originalPrice * $discount / 100);

        return [
            'destination_id' => Destination::factory(),
            'name' => $this->faker->company() . ' Hotel',
            'slug' => Str::slug($this->faker->unique()->company() . '-hotel'),
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
                'cancellation' => 'Flexible'
            ]),
            'cover_image' => "https://source.unsplash.com/800x600/?hotel," . $this->faker->word(),
            'avg_price_per_night' => round($finalPrice, 2),
            'avg_rating' => $this->faker->randomFloat(1, 2.5, 5.0),
            'reviews_count' => $this->faker->numberBetween(10, 2000),
        ];
    }
}
