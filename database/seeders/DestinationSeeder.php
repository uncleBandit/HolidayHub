<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10–15 dynamic destinations
        Destination::factory()->count(12)->create();

        // Optional: Create a few fixed popular destinations
        $popularDestinations = [
            [
                'name' => 'Paris, France',
                'slug' => 'paris-france',
                'country' => 'France',
                'city' => 'Paris',
                'tagline' => 'City of lights and romance',
                'description' => 'Explore the charming streets, iconic Eiffel Tower, and world-class cuisine.',
                'image_url' => 'https://source.unsplash.com/600x400/?paris,france',
                'is_featured' => true,
            ],
            [
                'name' => 'Maldives',
                'slug' => 'maldives',
                'country' => 'Maldives',
                'city' => null,
                'tagline' => 'Tropical paradise on earth',
                'description' => 'Relax on pristine beaches, swim in crystal-clear waters, and enjoy luxurious resorts.',
                'image_url' => 'https://source.unsplash.com/600x400/?maldives,beach',
                'is_featured' => true,
            ],
            [
                'name' => 'Tokyo, Japan',
                'slug' => 'tokyo-japan',
                'country' => 'Japan',
                'city' => 'Tokyo',
                'tagline' => 'A city of tradition and neon lights',
                'description' => 'Experience modern skyscrapers, ancient temples, and bustling street markets.',
                'image_url' => 'https://source.unsplash.com/600x400/?tokyo,japan',
                'is_featured' => true,
            ],
        ];

        foreach ($popularDestinations as $destination) {
            Destination::updateOrCreate(
                ['slug' => $destination['slug']],
                $destination
            );
        }

        $this->command->info('Destinations seeded successfully!');
    }
}
