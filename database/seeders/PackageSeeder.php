<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Destination;
use App\Models\Guest;
use App\Models\Package;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for required dependencies
        if (Agent::count() === 0 || Provider::count() === 0 || Destination::count() === 0) {
            $this->command->error('Please run AgentSeeder, ProviderSeeder, and DestinationSeeder first.');
            return;
        }

        $agents = Agent::all();
        $destinations = Destination::all();
        $guests = Guest::all();
        $guestUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'guest');
        })->get();

        if ($guests->isEmpty() || $guestUsers->isEmpty()) {
            $this->command->error('No guest records or guest users found. Please run the UserSeeder and GuestSeeder (if applicable) first.');
            return;
        }

        // Create 20 sample packages with random attributes
        Package::factory(20)
            ->create()
            ->each(function (Package $package) use ($agents, $destinations, $guests) {
                // Attach random relationships to the package
                $package->agent()->associate($agents->random())->save();
                //$package->provider()->associate($providers->random())->save();
                $package->destination()->associate($destinations->random())->save();

                // Create and attach multiple images to the package (polymorphic relationship)
                $package->images()->createMany([
                    ['path' => 'https://placehold.co/800x600/F472B6/fff?text=' . urlencode($package->title)],
                    ['path' => 'https://placehold.co/800x600/A78BFA/fff?text=' . urlencode('Gallery Image 1')],
                    ['path' => 'https://placehold.co/800x600/4ADE80/fff?text=' . urlencode('Gallery Image 2')],
                ]);

                // Create and attach multiple reviews to the package
                $package->reviews()->createMany([
                    ['guest_id' => $guests->random()->id, 'rating' => rand(4, 5), 'comment' => 'Absolutely loved this package!'],
                    ['guest_id' => $guests->random()->id, 'rating' => rand(3, 5), 'comment' => 'Great value and experience.'],
                ]);

                // Create and attach multiple features to the package
                $package->features()->createMany([
                    ['name' => 'Meals Included'],
                    ['name' => 'Guided Tour'],
                    ['name' => 'Airport Transfer'],
                ]);
            });
    }
}
