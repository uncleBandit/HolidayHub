<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLanguage>
 */
class ActivityLanguageFactory extends Factory
{
    protected $model = ActivityLanguage::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'language_code' => $this->faker->randomElement(['en', 'sw', 'fr', 'de']),
        ];
    }
}
