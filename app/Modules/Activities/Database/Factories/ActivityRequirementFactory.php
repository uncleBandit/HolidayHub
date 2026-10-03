<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityRequirement>
 */
class ActivityRequirementFactory extends Factory
{
    protected $model = ActivityRequirement::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'type' => 'equipment',
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'required' => true,
        ];
    }
}
