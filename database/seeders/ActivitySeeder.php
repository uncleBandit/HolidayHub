<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Destination; // Optional: if activities are tied to destinations
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all destinations (if you want activities tied to destinations)
        $destinations = Destination::all();

        foreach ($destinations as $destination) {
            // Create 3-7 activities per destination
            Activity::factory()
                ->count(rand(3, 7))
                ->for($destination) // Associate activity with destination
                ->create();
        }

        // Optional: create some general activities not tied to any destination
        Activity::factory()
            ->count(10)
            ->create();

        $this->command->info('Activities seeded successfully!');
    }
}
