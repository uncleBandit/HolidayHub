<?php

namespace App\Modules\Destinations\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 12 dynamic destinations using factory
        $destinations = Destination::factory()->count(12)->create();

        // Optional: fixed popular destinations

        $allDestinations = Destination::all();
        $bookableClasses = [Hotel::class, Villa::class, BedAndBreakfast::class];

        // Ensure Amenities exist before trying to attach them.
        if (Amenity::count() === 0) {
            $this->call(\App\Modules\Catalog\Database\Seeders\AmenitySeeder::class);
        }

        // Attach Hotels, Villas, B&Bs, and Packages for each destination
        foreach ($allDestinations as $destination) {
            $provider = Provider::inRandomOrder()->first();
            $agent = Agent::inRandomOrder()->first();

            // STEP 1: GUARANTEE one of each accommodation type for the destination
            foreach ($bookableClasses as $class) {
                // Use a dedicated method to create and attach accommodations to avoid code duplication
                $this->createAndAttachAccommodation($destination, $provider, $class);
            }

            // STEP 2: Create additional random accommodations for more variety
            $additionalAccommodations = rand(2, 5); // Create a few more random ones
            for ($i = 0; $i < $additionalAccommodations; $i++) {
                $randomClass = $bookableClasses[array_rand($bookableClasses)];
                $this->createAndAttachAccommodation($destination, $provider, $randomClass);
            }

            // STEP 3: Create holiday packages
            Package::factory()->count(rand(2, 4))->create([
                'destination_id' => $destination->id,
                'agent_id' => $agent->id,
            ]);
        }

        $this->command->info('Destinations seeded successfully!');
    }

    /**
     * Helper method to create and attach a bookable accommodation to a destination.
     */
    protected function createAndAttachAccommodation(Destination $destination, Provider $provider, string $bookableClass): void
    {
        $bookable = $bookableClass::factory()->create(['provider_id' => $provider->id,
            'is_active' => true,
            'is_verified' => true,
        ]);

        // Now, check the type and attach type-specific data
        if ($bookable instanceof Hotel) {
            $roomTypes = RoomType::factory()->count(rand(3, 5))->for($bookable)->create();
            foreach ($roomTypes as $roomType) {
                Room::factory()->count(rand(5, 20))->for($bookable)->for($roomType)->create();
            }
            $hotelAmenities = Amenity::inRandomOrder()->take(rand(5, 15))->pluck('id');
            $bookable->amenities()->attach($hotelAmenities);

        } elseif ($bookable instanceof Villa) {
            $villaAmenities = Amenity::inRandomOrder()->take(rand(3, 8))->pluck('id');
            $bookable->amenities()->attach($villaAmenities);
            $bookable->update(['max_guests' => rand(6, 12)]);

        } elseif ($bookable instanceof BedAndBreakfast) {
            $bnbAmenities = Amenity::inRandomOrder()->take(rand(2, 6))->pluck('id');
            $bookable->amenities()->attach($bnbAmenities);
            $bookable->update(['has_breakfast' => true]);
        }

        // Create the polymorphic accommodation entry
        Accommodation::factory()
            ->for($destination)
            ->withBookable($bookable)
            ->create();
    }
}
