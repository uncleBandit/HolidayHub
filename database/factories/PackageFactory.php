<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->city . ' ' . $this->faker->word . ' Getaway';
        $basePrice = $this->faker->randomFloat(2, 200, 2000);
        $discount = $this->faker->boolean(40) ? $this->faker->randomFloat(2, 100, $basePrice - 50) : null;

        return [
            // Core Info
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'short_description' => $this->faker->sentence(12),
            'full_description' => $this->faker->paragraphs(4, true),

            // Relations
            'destination_id' => Destination::query()->inRandomOrder()->value('id') ?? Destination::factory(),
            'agent_id' => Agent::query()->inRandomOrder()->value('id') ?? Agent::factory(),

            // Pricing
            'base_price' => $basePrice,
            'discount_price' => $discount,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'KES']),

            // Duration
            'duration_days' => $this->faker->numberBetween(3, 14),
            'duration_nights' => $this->faker->numberBetween(2, 13),

            // Package Features
            'inclusions' => json_encode([
                'Airport pickup',
                'Daily breakfast',
                'Guided city tour',
                'Free WiFi',
                'Access to pool & gym',
            ]),
            'exclusions' => json_encode([
                'International flights',
                'Visa fees',
                'Travel insurance',
                'Personal expenses',
            ]),
            'itinerary' => json_encode([
                ['day' => 1, 'title' => 'Arrival & hotel check-in', 'activities' => 'Airport pickup, welcome dinner'],
                ['day' => 2, 'title' => 'City tour', 'activities' => 'Historical sites, museum visit, local lunch'],
                ['day' => 3, 'title' => 'Beach/Adventure', 'activities' => 'Water sports or hiking trip'],
            ]),

            // Media
            'cover_image' => $this->faker->imageUrl(1200, 800, 'travel', true, 'Cover'),
            'gallery' => json_encode([
                $this->faker->imageUrl(1200, 800, 'hotel', true, 'Hotel'),
                $this->faker->imageUrl(1200, 800, 'beach', true, 'Beach'),
                $this->faker->imageUrl(1200, 800, 'food', true, 'Cuisine'),
            ]),

            // Ratings & Popularity
            'avg_rating' => $this->faker->randomFloat(1, 3, 5),
            'reviews_count' => $this->faker->numberBetween(0, 120),
            'is_featured' => $this->faker->boolean(20),
            'views' => $this->faker->numberBetween(50, 5000),

            // Availability
            'active' => true,
            'available_from' => $this->faker->dateTimeBetween('now', '+1 month'),
            'available_to' => $this->faker->dateTimeBetween('+2 months', '+1 year'),

            // Metadata
            'meta_data' => json_encode([
                'tags' => $this->faker->words(3),
                'theme' => $this->faker->randomElement(['Romantic', 'Adventure', 'Family', 'Luxury']),
                'season' => $this->faker->randomElement(['Summer', 'Winter', 'All year']),
            ]),
        ];
    }
}
