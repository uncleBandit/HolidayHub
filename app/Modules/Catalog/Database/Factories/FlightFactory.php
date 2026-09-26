<?php

namespace App\Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Domain\Models\Flight>
 */
class FlightFactory extends Factory
{
    protected $model = \App\Modules\Catalog\Domain\Models\Flight::class;

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
