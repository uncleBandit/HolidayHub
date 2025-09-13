<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;
use App\Models\RoomType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Room',
            'room_type_id' => RoomType::factory(),
            'room_number' => $this->faker->unique()->numberBetween(100, 500),
            'hotel_id' => null, // This will be handled in the seeder
            'is_available' => true,
        ];
    }
}
