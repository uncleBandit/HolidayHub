<?php

namespace App\Modules\Activities\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Activities\Domain\Models\Experience>
 */
class ExperienceFactory extends Factory
{
    protected $model = \App\Modules\Activities\Domain\Models\Experience::class;

    /**
     * Define the model's default state.
     *
     * This was an empty stub, so every Experience::factory()->create() either
     * failed on the not-null constraint for title or produced a row that could
     * not be priced, since Experience::getBasePrice() reads price and
     * getIncludedGuests() reads included_guests.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 30, 400);

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'city' => $this->faker->city(),
            'location' => $this->faker->address(),
            'category' => $this->faker->randomElement([
                'food', 'culture', 'outdoors', 'wellness', 'nightlife', 'adventure',
            ]),
            'duration' => $this->faker->numberBetween(1, 8),
            'price' => $price,
            'capacity' => $this->faker->numberBetween(1, 20),
            'cover_image' => $this->faker->imageUrl(),
            'featured' => $this->faker->boolean(20),
            'currency' => 'USD',
            // Matches the paid price by default, so an experience created from
            // this factory has no surprise guest surcharge unless asked for.
            'included_guests' => 1,
            'max_guests' => $this->faker->numberBetween(2, 20),
        ];
    }
}
