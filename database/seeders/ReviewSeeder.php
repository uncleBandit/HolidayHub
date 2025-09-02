<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Destination;
use App\Models\Hotel;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Seeding reviews...');

        // Ensure there are some reviewable models to attach reviews to
        if (Destination::count() === 0) {
            $this->command->line('No destinations found. Creating 10 new ones...');
            Destination::factory()->count(10)->create();
            $this->command->info('Created 10 destinations.');
        }

        if (Hotel::count() === 0) {
            $this->command->line('No hotels found. Creating 10 new ones...');
            Hotel::factory()->count(10)->create();
            $this->command->info('Created 10 hotels.');
        }

        // Create 50 random reviews, half of which are approved
        $this->command->line('Creating 25 random reviews...');
        Review::factory()->count(25)->create();
        $this->command->info('Created 25 pending reviews.');

        $this->command->line('Creating 25 approved reviews...');
        Review::factory()->count(25)->approved()->create();
        $this->command->info('Created 25 approved reviews.');

        $this->command->info('Review seeding complete!');
    }
}
