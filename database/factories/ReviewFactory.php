<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Review;
use App\Models\Hotel;
use App\Models\Destination;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        // Randomly pick a reviewable model type
        $reviewables = [
            Hotel::class,
            Destination::class,
            Activity::class,
        ];

        $reviewableType = $this->faker->randomElement($reviewables);
        $reviewableId = $reviewableType::inRandomOrder()->first()?->id ?? null;

        return [
            'guest_id' => Guest::inRandomOrder()->first()?->id ?? Guest::factory(),
            'reviewable_id' => $reviewableId,
            'reviewable_type' => $reviewableType,
            'rating' => $this->faker->numberBetween(1, 5),
            'title' => $this->faker->sentence(3, true),
            'comment' => $this->faker->paragraph(2, true),
            'type' => strtolower(class_basename($reviewableType)), // hotel, destination, activity
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'meta' => [
                'images' => $this->faker->randomElements([
                    $this->faker->imageUrl(640, 480, 'travel'),
                    $this->faker->imageUrl(640, 480, 'hotel'),
                    $this->faker->imageUrl(640, 480, 'nature')
                ], $this->faker->numberBetween(0, 3)),
                'tags' => $this->faker->words($this->faker->numberBetween(1, 5)),
            ],
        ];
    }

    /**
     * State for approved reviews
     */
    public function approved(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
