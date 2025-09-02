<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate 30 hotels with realistic data
        Hotel::factory()
            ->count(30)
            ->create()
            ->each(function ($hotel) {
                // Optionally, you can attach rooms for each hotel after hotel creation
                $hotel->rooms()->createMany(
                    \App\Models\Room::factory()
                        ->count(rand(3, 8))
                        ->make()
                        ->toArray()
                );

                // And optionally, add room prices for each room
                $hotel->rooms->each(function ($room) {
                    $room->prices()->createMany(
                        \App\Models\RoomPrice::factory()
                            ->count(rand(1, 3))
                            ->make()
                            ->toArray()
                    );
                });
            });

        $this->command->info('Hotels, rooms, and room prices seeded successfully!');
    }
}
