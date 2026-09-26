<?php

namespace App\Modules\Packages\Database\Factories;

use App\Modules\Packages\Domain\Models\PackageFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageFeature>
 */
class PackageFeatureFactory extends Factory
{
    protected $model = PackageFeature::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Meals', 'Activities', 'Transport', 'Amenities', 'Wellness'];

        return [
            'name' => $this->faker->sentence(3), // e.g. "Free Airport Pickup"
            'icon' => $this->faker->randomElement([
                'fa-utensils', 'fa-car', 'fa-swimmer', 'fa-bed', 'fa-spa',
            ]),
            'category' => $this->faker->randomElement($categories),
            'description' => $this->faker->optional()->paragraph(),
            'is_highlighted' => $this->faker->boolean(30), // 30% chance to be highlighted
            'metadata' => [
                'value' => $this->faker->word(),     // e.g. "unlimited"
                'extra' => $this->faker->sentence(), // optional UX notes
            ],

            // Polymorphic keys (filled later when attached to a model)
            'featureable_id' => null,
            'featureable_type' => null,
        ];
    }

    /**
     * State: highlighted features only.
     */
    public function highlighted(): static
    {
        return $this->state(fn () => [
            'is_highlighted' => true,
        ]);
    }
}
