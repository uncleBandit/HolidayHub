<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
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
            'hotel_id' => Hotel::factory(),
            'room_type_id' => fn (array $attributes) => RoomType::factory()
                ->create(['hotel_id' => $attributes['hotel_id']])
                ->id,
            'room_number' => fn (array $attributes) => ((int) Room::query()
                ->where('hotel_id', $attributes['hotel_id'])
                ->max('room_number')) + 1,
            'name' => $this->faker->unique()->words(2, true).' Room',
            'description' => $this->faker->optional()->paragraph(),
            'capacity' => $this->faker->numberBetween(1, 6),
            'beds' => $this->faker->numberBetween(1, 3),
            'bed_type' => $this->faker->randomElement(['single', 'double', 'queen', 'king', 'bunk']),
            'status' => 'available',
            'is_available' => true,
            'has_ac' => $this->faker->boolean(),
            'has_wifi' => $this->faker->boolean(80),
            'has_tv' => $this->faker->boolean(),
            'has_balcony' => $this->faker->boolean(),
            'thumbnail' => null,
            'gallery' => [],
        ];
    }
}
