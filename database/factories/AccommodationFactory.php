<?php

namespace Database\Factories;

use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccommodationFactory extends Factory
{
    protected $model = Accommodation::class;

    public function definition(): array
    {
        return [
            'destination_id' => null, // we always override in seeder
            'provider_id' => null,    // seeder will handle this
            'bookable_id' => null,
            'bookable_type' => null,
        ];
    }

    public function withBookable($bookable)
    {
        return $this->state([
            'bookable_id'   => $bookable->id,
            'bookable_type' => get_class($bookable),
            'provider_id'   => $bookable->provider_id,
        ]);
    }

    public function forDestination($destination)
    {
        return $this->state([
            'destination_id' => $destination->id,
        ]);
    }
}
