<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Destination;
use App\Models\Guest;
use App\Models\Provider;
use App\Models\User;
use App\Models\Villa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            ->each(function (Villa $villa) use ($amenities, $guests) {
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
                                'start_date'    => now()->addDays(rand(1, 30))->toDateString(),
                                'end_date'      => now()->addDays(rand(1, 30) + 7)->toDateString(), // 1 week window
                                'quantity'      => 1,
                                'status'        => 'available',
                                'price_per_night' => $villa->base_price + rand(20, 100),
                            ],
                            [
                                'start_date'    => now()->addDays(rand(31, 60))->toDateString(),
                                'end_date'      => now()->addDays(rand(31, 60) + 5)->toDateString(),
                                'quantity'      => 0,
                                'status'        => 'unavailable',
                                'price_per_night' => $villa->base_price,
                            ],
                        ]);

            });
    }
}
