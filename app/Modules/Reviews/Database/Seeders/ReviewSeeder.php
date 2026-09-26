<?php

namespace App\Modules\Reviews\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding reviews...');

        // Fetch existing guests and reviewable items to link reviews to
        $guests = Guest::all();
        $reviewables = collect()
            ->merge(Hotel::all())
            ->merge(Destination::all());

        // If there are no guests or reviewable items, we can't seed reviews.
        if ($guests->isEmpty() || $reviewables->isEmpty()) {
            $this->command->warn('Skipping review seeding. Guests or reviewable items are missing.');

            return;
        }

        // Create 25 pending reviews linked to random guests and reviewable items
        Review::factory()->count(25)->create([
            'status' => 'pending',
            'guest_id' => fn () => $guests->random()->id,
            'reviewable_id' => fn () => $reviewables->random()->id,
            'reviewable_type' => fn () => get_class($reviewables->random()),
        ]);
        $this->command->info('Created 25 pending reviews.');

        // Create 25 approved reviews linked to random guests and reviewable items
        Review::factory()->count(25)->create([
            'status' => 'approved',
            'guest_id' => fn () => $guests->random()->id,
            'reviewable_id' => fn () => $reviewables->random()->id,
            'reviewable_type' => fn () => get_class($reviewables->random()),
        ]);
        $this->command->info('Created 25 approved reviews.');

        $this->command->info('Review seeding complete!');
    }
}
