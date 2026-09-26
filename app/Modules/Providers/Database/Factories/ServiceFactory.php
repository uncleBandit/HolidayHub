<?php

namespace App\Modules\Providers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Providers\Domain\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = \App\Modules\Providers\Domain\Models\Service::class;

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
