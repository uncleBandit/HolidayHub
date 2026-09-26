<?php

namespace App\Modules\Accommodation\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Accommodation\Domain\Models\RoomPrice;
use Illuminate\Database\Seeder;

class RoomPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all rooms
        $rooms = Room::all();

        foreach ($rooms as $room) {
            // Each room gets 1-3 price entries, simulating seasonal rates
            RoomPrice::factory()
                ->count(rand(1, 3))
                ->for($room) // Associate with the current room
                ->create();
        }

        $this->command->info('Room prices seeded successfully for all rooms!');
    }
}
