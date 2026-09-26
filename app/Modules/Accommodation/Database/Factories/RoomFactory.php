<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Accommodation\Domain\Models\Room>
 */
class RoomFactory extends Factory
{
    protected $model = \App\Modules\Accommodation\Domain\Models\Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true).' Room',
            'room_type_id' => RoomType::factory(),
            'room_number' => $this->faker->numberBetween(100, 500),
            'hotel_id' => null, // This will be handled in the seeder
            'is_available' => true,
        ];
    }
}
