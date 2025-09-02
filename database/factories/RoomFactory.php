<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Hotel;

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
        $roomTypes = [
            'Standard Room',
            'Deluxe Room',
            'Executive Suite',
            'Presidential Suite',
            'Family Room',
            'Studio Apartment',
            'Penthouse'
        ];

        $bedTypes = ['single', 'double', 'queen', 'king', 'bunk'];

        return [
            'hotel_id'      => Hotel::factory(), // Attach room to a hotel
            'name'          => $this->faker->randomElement($roomTypes),
            'description'   => $this->faker->paragraph(3, true),
            'capacity'      => $this->faker->numberBetween(1, 6),
            'beds'          => $this->faker->numberBetween(1, 4),
            'bed_type'      => $this->faker->randomElement($bedTypes),
            'is_available'  => $this->faker->boolean(85),
            'has_ac'        => $this->faker->boolean(70),
            'has_wifi'      => $this->faker->boolean(90),
            'has_tv'        => $this->faker->boolean(60),
            'has_balcony'   => $this->faker->boolean(50),
            'thumbnail'     => $this->faker->imageUrl(800, 600, 'hotel', true, 'room'),
            'gallery'       => json_encode([
                $this->faker->imageUrl(800, 600, 'hotel', true, 'room1'),
                $this->faker->imageUrl(800, 600, 'hotel', true, 'room2'),
            ]),
        ];
    }
}
