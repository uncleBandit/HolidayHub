<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\ActivityCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ActivityCategory>
 */
class ActivityCategoryFactory extends Factory
{
    protected $model = ActivityCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
