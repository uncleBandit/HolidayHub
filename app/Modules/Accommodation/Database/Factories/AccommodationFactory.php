<?php

namespace App\Modules\Accommodation\Database\Factories;

use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class AccommodationFactory extends Factory
{
    protected $model = Accommodation::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Accommodation $accommodation): void {
            if ($accommodation->bookable_type && $accommodation->bookable_id) {
                return;
            }

            $hotel = Hotel::factory()->createQuietly([
                'provider_id' => $accommodation->provider_id,
            ]);

            $accommodation->bookable()->associate($hotel);
            $accommodation->name = $hotel->name;
            $accommodation->slug = $hotel->slug;
            $accommodation->description = $hotel->description;
            $accommodation->address = $hotel->address;
            $accommodation->city = $hotel->city;
            $accommodation->country = $hotel->country;
            $accommodation->policies = $hotel->policies;
        });
    }

    public function definition(): array
    {
        return [
            'destination_id' => Destination::factory(),
            'provider_id' => Provider::factory(),
            'bookable_id' => null,
            'bookable_type' => null,
            'status' => AccommodationStatus::Draft,
            'verification_status' => VerificationStatus::Unverified,
            'booking_mode' => 'instant',
        ];
    }

    public function withBookable(Model $bookable): static
    {
        return $this->state([
            'bookable_id' => $bookable->id,
            'bookable_type' => $bookable->getMorphClass(),
            'provider_id' => $bookable->getAttribute('provider_id'),
        ]);
    }

    public function forDestination(Destination $destination): static
    {
        return $this->state([
            'destination_id' => $destination->id,
        ]);
    }
}
