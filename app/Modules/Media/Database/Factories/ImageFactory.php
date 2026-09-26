<?php

namespace App\Modules\Media\Database\Factories;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Media\Domain\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Media\Domain\Models\Image>
 */
class ImageFactory extends Factory
{
    protected $model = \App\Modules\Media\Domain\Models\Image::class;

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
            'imageable_id' => null, // set explicitly in seeder or state
            'path' => 'images/'.class_basename($imageable).'/'.$this->faker->uuid().'.jpg',
            'alt_text' => $this->faker->sentence(6),
            'caption' => $this->faker->optional()->sentence(10),
            'order' => $this->faker->numberBetween(0, 10),
            'is_primary' => $this->faker->boolean(30), // 30% chance to be primary
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
