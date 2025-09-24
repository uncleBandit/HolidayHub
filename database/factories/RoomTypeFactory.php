<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Hotel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true) . ' Room';

        return [
        'name' => $name,
        'description' => $this->faker->paragraph(3),
        'price_per_night' => $this->faker->randomFloat(2, 100, 1000),
        'capacity' => $this->faker->numberBetween(1, 5),
        'beds' => $this->faker->numberBetween(1, 3),
        'hero_image_url' => 'https://picsum.photos/1200/800/?random=' . $this->faker->unique()->numberBetween(1, 1000) . '-' . Str::slug($name) . ',room',
        'slug' => Str::slug($name) . '-' . Str::uuid(),
        ];
    }
}
