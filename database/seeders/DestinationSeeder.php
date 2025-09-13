<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Destination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Hotel;
use App\Models\Villa;
use App\Models\BedAndBreakfast;
use App\Models\Package;
use App\Models\Provider;
use App\Models\User;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 12 dynamic destinations using factory
        $destinations =Destination::factory()->count(12)->create();

        // Optional: fixed popular destinations
        $popularDestinations = [
            [
                'name' => 'Paris, France',
                'slug' => 'paris-france',
                'country' => 'France',
                'city' => 'Paris',
                'tagline' => 'City of lights and romance',
                'description' => 'Explore the charming streets, iconic Eiffel Tower, and world-class cuisine.',
                'thumbnail' => 'https://source.unsplash.com/800x600/?paris,france',
                'gallery' => [
                    'https://source.unsplash.com/800x600/?paris1',
                    'https://source.unsplash.com/800x600/?paris2',
                    'https://source.unsplash.com/800x600/?paris3',
                ],
                'average_cost' => 2000,
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'popularity_score' => 1000,
                'is_featured' => true,
                'best_season' => 'April - June',
                'highlights' => ['Eiffel Tower', 'Museums', 'Cafes'],
                'meta_title' => 'Visit Paris, France',
                'meta_description' => 'Experience the romance and culture of Paris.',
                'tags' => ['romance', 'culture', 'city', 'Europe'],
            ],
            [
                'name' => 'Maldives',
                'slug' => 'maldives',
                'country' => 'Maldives',
                'city' => null,
                'tagline' => 'Tropical paradise on earth',
                'description' => 'Relax on pristine beaches, swim in crystal-clear waters, and enjoy luxurious resorts.',
                'thumbnail' => 'https://source.unsplash.com/800x600/?maldives,beach',
                'gallery' => [
                    'https://source.unsplash.com/800x600/?maldives1',
                    'https://source.unsplash.com/800x600/?maldives2',
                    'https://source.unsplash.com/800x600/?maldives3',
                ],
                'average_cost' => 3500,
                'latitude' => 3.2028,
                'longitude' => 73.2207,
                'popularity_score' => 950,
                'is_featured' => true,
                'best_season' => 'November - April',
                'highlights' => ['Beaches', 'Snorkeling', 'Luxury Resorts'],
                'meta_title' => 'Visit Maldives',
                'meta_description' => 'Discover the ultimate tropical getaway in the Maldives.',
                'tags' => ['beach', 'luxury', 'island', 'tropical'],
            ],
            [
                'name' => 'Tokyo, Japan',
                'slug' => 'tokyo-japan',
                'country' => 'Japan',
                'city' => 'Tokyo',
                'tagline' => 'A city of tradition and neon lights',
                'description' => 'Experience modern skyscrapers, ancient temples, and bustling street markets.',
                'thumbnail' => 'https://source.unsplash.com/800x600/?tokyo,japan',
                'gallery' => [
                    'https://source.unsplash.com/800x600/?tokyo1',
                    'https://source.unsplash.com/800x600/?tokyo2',
                    'https://source.unsplash.com/800x600/?tokyo3',
                ],
                'average_cost' => 1800,
                'latitude' => 35.6895,
                'longitude' => 139.6917,
                'popularity_score' => 900,
                'is_featured' => true,
                'best_season' => 'March - May',
                'highlights' => ['Temples', 'Skyscrapers', 'Street Markets'],
                'meta_title' => 'Visit Tokyo, Japan',
                'meta_description' => 'Explore the vibrant culture and modernity of Tokyo.',
                'tags' => ['city', 'culture', 'modern', 'Japan'],
            ],
        ];

        foreach ($popularDestinations as $destination) {
            // Convert JSON fields to strings for DB insertion
            $destination['gallery'] = json_encode($destination['gallery']);
            $destination['highlights'] = json_encode($destination['highlights']);
            $destination['tags'] = json_encode($destination['tags']);

            Destination::updateOrCreate(
                ['slug' => $destination['slug']],
                $destination
            );
        }


        // Attach Hotels, Villas, B&Bs, and Packages for each destination
        foreach ($destinations as $destination) {

            $provider = Provider::inRandomOrder()->first() ;
            $agent    = Agent::inRandomOrder()->first() ;
            // Create hotels
            Hotel::factory()->count(rand(2, 5))->create([
                'destination_id' => $destination->id,
                'provider_id' => $provider->id,
            ]);

            // Create villas
            Villa::factory()->count(rand(1, 3))->create([
                'destination_id' => $destination->id,
                'provider_id' => $provider->id,
            ]);

            // Create B&Bs
            BedAndBreakfast::factory()->count(rand(1, 3))->create([
                'destination_id' => $destination->id,
                'provider_id' => $provider->id,
            ]);

            // Create holiday packages
            Package::factory()->count(rand(2, 4))->create([
                'destination_id' => $destination->id,
                'agent_id' => $agent->id,
            ]);
        }


        $this->command->info('Destinations seeded successfully!');
    }
}
