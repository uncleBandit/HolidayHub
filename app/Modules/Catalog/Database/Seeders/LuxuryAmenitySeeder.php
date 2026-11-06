<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Domain\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The facilities and in-room touches a full-service, up-to-five-star hotel is
 * expected to be able to list. Property-wide facilities are `general`, in-room
 * ones are `room`, matching AmenitySeeder. Idempotent (firstOrCreate).
 */
class LuxuryAmenitySeeder extends Seeder
{
    private const AMENITIES = [
        'general' => [
            'Spa & Wellness Centre' => 'Treatment rooms, steam and sauna.',
            'Concierge' => 'Restaurant bookings, tours and local know-how.',
            '24-Hour Room Service' => 'In-room dining at any hour.',
            'Airport Transfer' => 'Arranged pick-up and drop-off.',
            'Restaurant' => 'On-site dining for breakfast, lunch and dinner.',
            'Bar & Lounge' => 'Cocktails and a quiet place to unwind.',
            'Beach Access' => 'Direct access to the beach.',
            'Kids’ Club' => 'Supervised activities for younger guests.',
            'Business Centre' => 'Workspace, printing and meeting rooms.',
            'Valet Parking' => 'Your car parked and returned by our team.',
            'Laundry & Dry Cleaning' => 'Same-day laundry and pressing.',
            'Accessible Facilities' => 'Step-free access and adapted public areas.',
        ],
        'room' => [
            'Butler Service' => 'A dedicated butler for the room.',
            'Rain Shower' => 'Walk-in rain shower.',
            'Soaking Bathtub' => 'Deep freestanding bathtub.',
            'Nespresso Machine' => 'Coffee machine with capsules.',
            'Bathrobes & Slippers' => 'Robes and slippers provided.',
            'In-Room Safe' => 'Laptop-sized electronic safe.',
            'Blackout Curtains' => 'Full blackout for undisturbed sleep.',
            'Premium Toiletries' => 'Branded bath amenities.',
            'Turndown Service' => 'Evening turndown each night.',
            'Pillow Menu' => 'A choice of pillows on request.',
            'Air Conditioning' => 'Individually controlled climate.',
            'Free In-Room Wi-Fi' => 'High-speed wireless in the room.',
        ],
    ];

    public function run(): void
    {
        foreach (self::AMENITIES as $type => $items) {
            foreach ($items as $name => $description) {
                $slug = Str::slug($name);

                // Slugs are unique across types, so a room-level twin of a
                // property-wide amenity gets its own slug.
                if (Amenity::where('slug', $slug)->where('type', '!=', $type)->exists()) {
                    $slug .= '-'.$type;
                }

                Amenity::firstOrCreate(
                    ['name' => $name, 'type' => $type],
                    ['slug' => $slug, 'description' => $description, 'icon' => null, 'active' => true]
                );
            }
        }
    }
}
