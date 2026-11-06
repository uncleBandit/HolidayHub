<?php

namespace App\Modules\Packages\Database\Factories;

use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Domain\Models\PackageComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackageComponent>
 */
class PackageComponentFactory extends Factory
{
    protected $model = PackageComponent::class;

    /**
     * An agent-priced transfer: the one kind that needs no listing behind it.
     * Use the stay()/experience() states or set componentable_* for listings.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'day' => 1,
            'sequence' => 1,
            'kind' => 'transfer',
            'title' => $this->faker->randomElement(['Airport transfer', 'Private driver to the coast', 'Safari jeep pickup']),
            'description' => $this->faker->optional()->sentence(),
            'quantity' => 1,
            'price' => $this->faker->randomFloat(2, 20, 200),
            'currency' => 'USD',
        ];
    }

    /** Free time on a given day. */
    public function free(int $day = 1): static
    {
        return $this->state(fn () => ['kind' => 'free', 'day' => $day, 'title' => 'Free time', 'price' => null]);
    }
}
