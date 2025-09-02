<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomPrice>
 */
class RoomPriceFactory extends Factory
{
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+6 months');
        $endDate = (clone $startDate)->modify('+'.rand(1, 14).' days');

        $basePrice = $this->faker->randomFloat(2, 50, 500);

        $discount = $this->faker->boolean(50) ? $this->faker->randomFloat(2, 5, 50) : 0;
        $discountPrice = $discount > 0 ? round($basePrice - ($basePrice * $discount / 100), 2) : null;

        $meta = [
            'is_peak_season' => $this->faker->boolean(30),
            'is_weekend_rate' => $this->faker->boolean(40),
            'notes' => $this->faker->optional()->sentence(6),
        ];

        return [
            'room_id'        => Room::factory(),
            'base_price'     => $basePrice,
            'discount_price' => $discountPrice,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'meta'           => json_encode($meta),
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }
}
