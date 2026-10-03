<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityOption>
 */
class ActivityOptionFactory extends Factory
{
    protected $model = ActivityOption::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'duration_minutes' => $this->faker->numberBetween(60, 360),
            'base_price' => $this->faker->randomFloat(2, 20, 500),
            'currency' => 'KES',
            'included_participants' => 1,
            'max_participants' => $this->faker->numberBetween(2, 20),
            'booking_mode' => 'shared',
            'is_active' => true,
        ];
    }
}
