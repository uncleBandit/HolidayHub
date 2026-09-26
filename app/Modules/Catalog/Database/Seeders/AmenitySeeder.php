<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Domain\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            'general' => [
                ['name' => 'Free Wi-Fi', 'icon' => 'amenities/icons/wifi.svg', 'description' => 'High-speed wireless internet throughout the property.'],
                ['name' => 'Air Conditioning', 'icon' => 'amenities/icons/aircon.svg', 'description' => 'Climate-controlled comfort in all rooms.'],
                ['name' => 'Parking', 'icon' => 'amenities/icons/parking.svg', 'description' => 'Secure on-site parking available.'],
                ['name' => 'Swimming Pool', 'icon' => 'amenities/icons/pool.svg', 'description' => 'Outdoor and indoor pools with lounging areas.'],
                ['name' => 'Fitness Center', 'icon' => 'amenities/icons/gym.svg', 'description' => 'Modern gym equipment for guests.'],
                ['name' => 'Pet Friendly', 'icon' => 'amenities/icons/pet.svg', 'description' => 'Pets are welcome with prior notice.'],
                ['name' => '24/7 Reception', 'icon' => 'amenities/icons/reception.svg', 'description' => 'Round-the-clock reception and concierge services.'],
            ],

            'room' => [
                ['name' => 'Private Balcony', 'icon' => 'amenities/icons/balcony.svg', 'description' => 'Enjoy views from your private balcony.'],
                ['name' => 'Room Service', 'icon' => 'amenities/icons/room-service.svg', 'description' => 'Meals and drinks delivered to your room.'],
                ['name' => 'Mini Bar', 'icon' => 'amenities/icons/minibar.svg', 'description' => 'In-room stocked mini bar.'],
                ['name' => 'Smart TV', 'icon' => 'amenities/icons/tv.svg', 'description' => 'Access to streaming services and channels.'],
                ['name' => 'Jacuzzi', 'icon' => 'amenities/icons/jacuzzi.svg', 'description' => 'Private in-room hot tub.'],
                ['name' => 'Work Desk', 'icon' => 'amenities/icons/desk.svg', 'description' => 'Spacious work area with ergonomic chair.'],
            ],

            'villa' => [
                ['name' => 'Private Pool', 'icon' => 'amenities/icons/private-pool.svg', 'description' => 'Exclusive pool for villa guests only.'],
                ['name' => 'Chef Service', 'icon' => 'amenities/icons/chef.svg', 'description' => 'Private chef available upon request.'],
                ['name' => 'Ocean View', 'icon' => 'amenities/icons/ocean-view.svg', 'description' => 'Uninterrupted views of the ocean.'],
                ['name' => 'Garden', 'icon' => 'amenities/icons/garden.svg', 'description' => 'Beautiful landscaped garden around the villa.'],
                ['name' => 'BBQ Area', 'icon' => 'amenities/icons/bbq.svg', 'description' => 'Outdoor BBQ facilities available.'],
                ['name' => 'Private Parking', 'icon' => 'amenities/icons/private-parking.svg', 'description' => 'Dedicated villa parking spot.'],
            ],

            'package' => [
                ['name' => 'Guided Tours', 'icon' => 'amenities/icons/tour.svg', 'description' => 'Expert-guided local tours included.'],
                ['name' => 'All-Inclusive Meals', 'icon' => 'amenities/icons/meals.svg', 'description' => 'Breakfast, lunch, and dinner included.'],
                ['name' => 'Airport Transfers', 'icon' => 'amenities/icons/transfer.svg', 'description' => 'Complimentary airport pickup & drop-off.'],
                ['name' => 'Adventure Activities', 'icon' => 'amenities/icons/adventure.svg', 'description' => 'Includes hiking, rafting, or safaris.'],
                ['name' => 'Wellness & Spa', 'icon' => 'amenities/icons/spa.svg', 'description' => 'Relax with massages, sauna, and spa access.'],
                ['name' => 'Cultural Experiences', 'icon' => 'amenities/icons/culture.svg', 'description' => 'Authentic local cultural activities.'],
            ],
        ];

        foreach ($amenities as $type => $items) {
            foreach ($items as $item) {
                Amenity::firstOrCreate(
                    ['name' => $item['name'], 'type' => $type],
                    [
                        'slug' => Str::slug($item['name']), // unique slug for future lookups
                        'description' => $item['description'],
                        'icon' => $item['icon'],
                        'active' => true,
                    ]
                );
            }
        }

        // Add an inactive amenity for testing
        Amenity::firstOrCreate(
            ['name' => 'Test Disabled Amenity', 'type' => 'general'],
            [
                'slug' => 'test-disabled-amenity',
                'description' => 'This amenity is inactive for testing purposes.',
                'icon' => 'amenities/icons/disabled.svg',
                'active' => false,
            ]
        );
    }
}
