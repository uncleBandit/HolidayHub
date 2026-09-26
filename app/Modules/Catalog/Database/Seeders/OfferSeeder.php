<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for necessary dependencies
        if (Destination::count() === 0 || Provider::count() === 0) {
            $this->command->warn('⚠️  Please seed destinations and providers before seeding offers.');

            return;
        }

        // Define the offerable models and the number of instances to create for each
        $offerableModels = [
            Hotel::class => 5, // Create 5 hotels
            Villa::class => 3, // Create 3 villas
            Package::class => 10, // Create 10 packages
            RoomType::class => 15, // Create 15 room types
            BedAndBreakfast::class => 2, // Create 2 bed and breakfasts
        ];

        // Loop through each offerable model and create offers
        foreach ($offerableModels as $modelClass => $count) {
            // Create a specific number of instances for the current offerable model
            $offerables = $modelClass::factory()->count($count)->create();

            // Create a corresponding offer for each newly created offerable
            $offerables->each(function ($offerable) {
                // We create the Offer and explicitly set the polymorphic relationship.
                // The OfferFactory will handle the rest of the attributes.
                Offer::factory()->create([
                    'offerable_id' => $offerable->id,
                    'offerable_type' => $offerable::class,
                ]);
            });
        }

        $this->command->info('✅ Offers seeded successfully!');
    }
}
