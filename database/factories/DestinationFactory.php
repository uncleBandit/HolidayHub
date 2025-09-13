<?php

namespace Database\Factories;

use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Destination>
 */
class DestinationFactory extends Factory
{
    protected $model = Destination::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city . ', ' . $this->faker->country,
            'slug' => Str::slug($this->faker->unique()->city . '-' . $this->faker->country . '-' . $this->faker->randomNumber()),
            'country' => $this->faker->country,
            'city' => $this->faker->city,
            'description' => $this->faker->paragraphs(3, true),
            'thumbnail' => $this->faker->imageUrl(800, 600, 'travel'),
            'gallery' => [   // keep as array, Laravel will cast
                $this->faker->imageUrl(800, 600, 'travel'),
                $this->faker->imageUrl(800, 600, 'travel'),
            ],
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'popularity_score' => $this->faker->numberBetween(100, 1000),
            'is_featured' => $this->faker->boolean,
            'best_season' => $this->faker->word,
            'highlights' => $this->faker->words(3), // array of strings
            'meta_data' => [
                'meta_title' => $this->faker->sentence,
                'meta_description' => $this->faker->paragraph,
            ],
            'tags' => $this->faker->words(4), // array of strings
            'average_cost' => $this->faker->numberBetween(500, 5000),
        ];
    }
}
