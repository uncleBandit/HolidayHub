<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySession>
 */
class ActivitySessionFactory extends Factory
{
    protected $model = ActivitySession::class;

    public function definition(): array
    {
        $startsAt = now()->addDays(7)->setTime(9, 0);

        return [
            'activity_id' => Activity::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(3),
            'timezone' => 'Africa/Nairobi',
            'capacity' => 12,
            'booked_capacity' => 0,
            'status' => 'scheduled',
        ];
    }
}
