<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityItineraryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityItineraryItem>
 */
class ActivityItineraryItemFactory extends Factory
{
    protected $model = ActivityItineraryItem::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'sequence' => 1,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'duration_minutes' => 30,
        ];
    }
}
