<?php

namespace App\Modules\Availability\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Availability\Domain\Models\Availability>
 */
class AvailabilityFactory extends Factory
{
    protected $model = \App\Modules\Availability\Domain\Models\Availability::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
