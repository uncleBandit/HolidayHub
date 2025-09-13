<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Destination;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Randomly choose a polymorphic parent
        $imageable = $this->faker->randomElement([
            Hotel::class,
            Room::class,
            Destination::class,
        ]);

        return [
            'imageable_type' => $imageable,
            'imageable_id'   => null, // set explicitly in seeder or state
            'path' => 'images/' . class_basename($imageable) . '/' . $this->faker->uuid() . '.jpg',
            'alt_text'       => $this->faker->sentence(6),
            'caption'        => $this->faker->optional()->sentence(10),
            'order'          => $this->faker->numberBetween(0, 10),
            'is_primary'     => $this->faker->boolean(30), // 30% chance to be primary
        ];
    }

    /**
     * Set image as primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'order' => 0,
        ]);
    }

    /**
     * Assign a specific imageable parent.
     */
    public function forImageable(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => $type,
            'imageable_id' => $id,
        ]);
    }
}
