<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\AccommodationVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccommodationVerification>
 */
class AccommodationVerificationFactory extends Factory
{
    protected $model = AccommodationVerification::class;

    public function definition(): array
    {
        return [
            'accommodation_id' => Accommodation::factory(),
            'reviewer_id' => null,
            'status' => 'pending',
            'reason' => null,
            'metadata' => [],
        ];
    }
}
