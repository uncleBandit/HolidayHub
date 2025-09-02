<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all hotels
        $hotels = Hotel::all();

        foreach ($hotels as $hotel) {
            // Each hotel gets 5-15 rooms
            Room::factory()
                ->count(rand(5, 15))
                ->for($hotel) // Associate room with the current hotel
                ->create();
        }

        $this->command->info('Rooms seeded successfully for all hotels!');
    }
}
