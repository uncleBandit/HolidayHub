<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Image;
use App\Models\Provider;
use App\Models\Review;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;


class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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

        // Create 30 hotels and attach all relationships
        Hotel::factory(30)
            ->recycle($providers)
            ->create(['provider_id' => $providers->random()->id,
                      'destination_id' => $destinations->random()->id])
            ->each(function (Hotel $hotel) use ($amenities, $destinations, $guests) {

                // Associate with a random destination
                $hotel->destination()->associate($destinations->random())->save();

                // Attach a random subset of amenities (polymorphic many-to-many)
                $hotel->amenities()->attach(
                    $amenities->random(rand(5, 10))->pluck('id')->toArray()
                );

                // Add 1 to 5 reviews to each hotel
                // This is a more explicit and reliable way to create reviews
                $numberOfReviews = rand(1, 5);
                for ($i = 0; $i < $numberOfReviews; $i++) {
                    $hotel->reviews()->create([
                        'guest_id' => $guests->random()->id, // Ensure a valid guest ID is used
                        'rating' => rand(3, 5),
                        'comment' => Arr::random([
                            'Amazing experience!',
                            'Would definitely recommend this hotel.',
                            'The staff was very friendly and the rooms were clean.',
                            'A perfect place for a quiet getaway.',
                        ]),
                    ]);
                }


                    // Make sure hotel is saved
                    $hotel->refresh(); // optional, ensures $hotel->id exists

                    // Get all guest IDs
                    $guestIds = Guest::pluck('id')->toArray();

                    if (!empty($guestIds)) {
                    // Pick 1-3 random guest IDs
                    $randomGuestIds = Arr::random($guestIds, rand(1, min(3, count($guestIds))));

                        // Attach to the hotel wishlist with pivot 'added_at'
                        $hotel->wishlistedByUsers()->attach($randomGuestIds, [
                            'added_at' => now(),
                        ]);
                    }



                // Create a few images for the hotel gallery
                Image::factory(rand(3, 8))->create([
                    'imageable_id' => $hotel->id,
                    'imageable_type' => Hotel::class,
                ]);

                // Create 2 to 5 room types for the hotel
                RoomType::factory(rand(2, 5))
                    ->create(['hotel_id' => $hotel->id,
                           // 'slug' => fn (array $attributes) => Str::slug($attributes['name']) . '-' . $hotel->id . '-' . uniqid(),
                    ])
                    ->each(function (RoomType $roomType) use ($hotel,$guests) {

                        // Generate a unique slug per hotel
                       // $roomType->slug = Str::slug($roomType->name) . '-' . $hotel->id . '-' . uniqid();
                       // $roomType->save();
                        // For each room type, create 2 to 5 actual rooms
                        $rooms = Room::factory(rand(2, 5))
                            ->create(['room_type_id' => $roomType->id ,
                                      'hotel_id' => $hotel->id]);

                        // Add a booking for one of the rooms to test availability
                        $randomRoom = $rooms->random();
                        $checkIn = Carbon::now()->addDays(rand(1, 10));
                        $checkOut = $checkIn->copy()->addDays(rand(1, 5));
                        $guest = Guest::inRandomOrder()->first(); // Ensure it exists in guests table
                        Booking::factory()->create([
                                            'guest_id' => $guest->id,
                                            'bookable_type' => Room::class,
                                            'bookable_id' => $randomRoom->id,
                                            'check_in_date' => $checkIn,
                                            'check_out_date' => $checkOut,
                        ]);

                    });
            });

        $this->command->info('Hotels and all associated data seeded successfully!');
    }
}
