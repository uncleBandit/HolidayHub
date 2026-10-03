<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLocation>
 */
class ActivityLocationFactory extends Factory
{
    protected $model = ActivityLocation::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'type' => 'meeting_point',
            'name' => $this->faker->streetName(),
            'address' => $this->faker->address(),
            'sequence' => 0,
        ];
    }
}
