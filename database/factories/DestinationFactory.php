<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Destination>
 */
class DestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = $this->faker->city();
        $country = $this->faker->country();
        $name = "{$city}, {$country}";

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1000, 999999),
            'country' => $country,           // REQUIRED
            'city' => $city,                 // REQUIRED if you want
            'short_description' => $this->faker->sentence(8),
            'description' => $this->faker->paragraphs(3, true),
            'thumbnail' => "https://source.unsplash.com/800x600/?travel," . Str::slug($city),
            'gallery' => [
                "https://source.unsplash.com/800x600/?travel," . Str::slug($city) . "1",
                "https://source.unsplash.com/800x600/?travel," . Str::slug($city) . "2"
            ],
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'popularity_score' => $this->faker->numberBetween(0, 1000),
            'is_featured' => $this->faker->boolean(40),
            'best_season' => $this->faker->randomElement(['Summer', 'Winter', 'Spring', 'Autumn', 'Year-round']),
            'highlights' => $this->faker->words(4),
            'meta_title' => $name,
            'meta_description' => $this->faker->sentence(12),
            'tags' => $this->faker->words(5),
            'average_cost' => $this->faker->numberBetween(500, 5000),
        ];
    }
}
