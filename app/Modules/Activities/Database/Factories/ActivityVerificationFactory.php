<?php

namespace App\Modules\Activities\Database\Factories;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityVerification>
 */
class ActivityVerificationFactory extends Factory
{
    protected $model = ActivityVerification::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'status' => 'pending',
            'provider_documents' => [],
            'checks' => [],
        ];
    }
}
