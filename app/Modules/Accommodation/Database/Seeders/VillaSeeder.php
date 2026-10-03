<?php

namespace App\Modules\Accommodation\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Seeder;

class VillaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for required dependencies
        if (Provider::count() === 0 || Destination::count() === 0 || Amenity::count() === 0) {
            $this->command->error('Please run ProviderSeeder, DestinationSeeder, and AmenitySeeder first.');

            return;
        }

        $amenities = Amenity::all();
        $providers = Provider::all();
        $destinations = Destination::all();
        $guests = Guest::all();
        $guestUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'guest');
        })->get();

        if ($guests->isEmpty() || $guestUsers->isEmpty()) {
            $this->command->error('No guest records or guest users found. Please run the UserSeeder and GuestSeeder (if applicable) first.');

            return;
        }

        // Create 15 sample villas with random attributes
        Villa::factory(15)
            ->recycle($providers)
            ->recycle($destinations)
            ->create()
            ->each(function (Villa $villa) use ($amenities, $destinations, $guests) {
                $villa->accommodation()->update(['destination_id' => $destinations->random()->id]);
                // Attach a random subset of amenities to each villa
                $villa->amenities()->attach(
                    $amenities->random(rand(3, 8))->pluck('id')->toArray()
                );

                // Add 1 to 5 reviews for each villa to demonstrate ratings
                $numberOfReviews = rand(1, 5);
                for ($i = 0; $i < $numberOfReviews; $i++) {
                    $villa->reviews()->create([
                        'guest_id' => $guests->random()->id,
                        'rating' => rand(3, 5), // Reviews will be decent
                        'comment' => 'This villa exceeded all my expectations. Highly recommended!',
                    ]);
                }

                // Add some availability records to show dynamic pricing
                $villa->availabilities()->createMany([
                    [
                        'start_date' => now()->addDays(rand(1, 30))->toDateString(),
                        'end_date' => now()->addDays(rand(1, 30) + 7)->toDateString(), // 1 week window
                        'quantity' => 1,
                        'status' => 'available',
                        'price_per_night' => $villa->avg_price_per_night + rand(20, 100),
                    ],
                    [
                        'start_date' => now()->addDays(rand(31, 60))->toDateString(),
                        'end_date' => now()->addDays(rand(31, 60) + 5)->toDateString(),
                        'quantity' => 0,
                        'status' => 'unavailable',
                        'price_per_night' => $villa->avg_price_per_night,
                    ],
                ]);

                $villa->forceFill(['is_active' => true, 'is_verified' => true])->save();
                $accommodation = $villa->accommodation()->firstOrFail();
                $accommodation->update([
                    'status' => AccommodationStatus::Published,
                    'verification_status' => VerificationStatus::Approved,
                    'verified_at' => now(),
                    'published_at' => now(),
                ]);
                $accommodation->verificationHistory()->create([
                    'status' => VerificationStatus::Approved->value,
                    'reason' => 'Seeded verified sample listing.',
                ]);
            });
    }
}
