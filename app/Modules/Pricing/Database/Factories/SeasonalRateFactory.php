<?php

namespace App\Modules\Pricing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Pricing\Domain\Models\SeasonalRate>
 */
class SeasonalRateFactory extends Factory
{
    protected $model = \App\Modules\Pricing\Domain\Models\SeasonalRate::class;

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
