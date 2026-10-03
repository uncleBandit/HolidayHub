<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySchedule>
 */
class ActivityScheduleFactory extends Factory
{
    protected $model = ActivitySchedule::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'timezone' => 'Africa/Nairobi',
            'capacity' => $this->faker->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
