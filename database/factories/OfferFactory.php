<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\Offer;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now()->addDays(rand(1, 30));
        $endDate = $startDate->copy()->addDays(rand(30, 180));
        $title = $this->faker->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph,
            'main_image' => 'https://source.unsplash.com/800x600/?holiday,travel,' . $this->faker->word,
            'gallery_images' => json_encode($this->faker->randomElements([
                'https://source.unsplash.com/800x600/?beach,travel',
                'https://source.unsplash.com/800x600/?mountain,adventure',
                'https://source.unsplash.com/800x600/?hotel,room',
                'https://source.unsplash.com/800x600/?food,dining',
            ], rand(2, 4))),
            'price' => $this->faker->randomFloat(2, 50, 2000),
            'discount_percent' => $this->faker->boolean(30) ? $this->faker->numberBetween(5, 50) : 0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_featured' => $this->faker->boolean(20),
            'active' => true,
            'max_capacity' => $this->faker->numberBetween(1, 50),
            'rating' => $this->faker->randomFloat(1, 3.0, 5.0),
            'tags' => json_encode($this->faker->randomElements(['family', 'luxury', 'romantic', 'adventure', 'beach', 'city', 'nature'], rand(1, 3))),

            // The 'offerable' polymorphic relationship should be handled manually
            // in the seeder or when you create the offer instance. This factory
            // will not create the related model automatically.
            'offerable_id' => null, // Placeholder
            'offerable_type' => null, // Placeholder

            // The foreign keys should also be set in the seeder to ensure
            // they reference existing records.
            'destination_id' => Destination::factory(),
            'provider_id' => Provider::factory(),
        ];
    }
}
