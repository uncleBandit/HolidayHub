<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\Testimonial;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Destinations + DestinationReviews
        $this->call([
            DestinationSeeder::class,

        ]);

        // Hotels + Rooms + RoomPrices + HotelReviews
        $this->call([
            HotelSeeder::class,
            RoomSeeder::class,
            RoomPriceSeeder::class,

        ]);

        // Activities + ActivityReviews
        $this->call([
            ActivitySeeder::class,

        ]);

        // Bookings
        $this->call([
            BookingSeeder::class,
        ]);



        $this->call([
            ReviewSeeder::class,
            RolesSeeder::class,
            ImageSeeder::class,
        ]);

        // Create Guests
        $guests = Guest::factory(30)->create();

        // Create Reviews for Hotels, Destinations, Activities
        foreach ($guests as $guest) {
            // Each guest leaves 1–5 reviews
            $numReviews = rand(1, 5);
            for ($i = 0; $i < $numReviews; $i++) {
                Review::factory()->for($guest)->create();
            }


        }

        $this->command->info('✅ Database seeded with guests, hotels, destinations, activities, and reviews!');
    }
    }

