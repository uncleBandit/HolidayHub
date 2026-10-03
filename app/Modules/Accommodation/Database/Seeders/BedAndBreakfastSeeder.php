<?php

namespace App\Modules\Accommodation\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BedAndBreakfastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure prerequisites
        if (Provider::count() === 0 || Destination::count() === 0 || Amenity::count() === 0) {
            $this->command->error('Please run ProviderSeeder, DestinationSeeder, and AmenitySeeder first.');

            return;
        }

        $amenities = Amenity::all();
        $providers = Provider::all();
        $destinations = Destination::all();

        $guests = Guest::all();
        $guestUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'guest');
        })->get();

        if ($guests->isEmpty() || $guestUsers->isEmpty()) {
            $this->command->error('No guest records or guest users found. Please run the UserSeeder and GuestSeeder (if applicable) first.');

            return;
        }

        $sampleBnbs = [
            [
                'name' => 'Cozy Cottage B&B',
                'description' => 'A charming countryside cottage with homemade breakfast and stunning views.',
                'price_per_night' => 80,
                'max_guests' => 4,
                'address' => '123 Country Lane',
                'city' => 'Nairobi',
                'country' => 'Kenya',
                'gallery' => [
                    'bnb/cottage1.jpg',
                    'bnb/cottage2.jpg',
                    'bnb/cottage3.jpg',
                ],
            ],
            [
                'name' => 'Seaside Escape B&B',
                'description' => 'Wake up to the sound of waves in this beautiful seaside retreat.',
                'price_per_night' => 120,
                'max_guests' => 6,
                'address' => '456 Ocean Drive',
                'city' => 'Mombasa',
                'country' => 'Kenya',
                'gallery' => [
                    'bnb/seaside1.jpg',
                    'bnb/seaside2.jpg',
                ],
            ],
            [
                'name' => 'Urban Charm B&B',
                'description' => 'Modern comfort in the heart of the city, with fresh breakfasts served daily.',
                'price_per_night' => 100,
                'max_guests' => 3,
                'address' => '789 Downtown Ave',
                'city' => 'Kisumu',
                'country' => 'Kenya',
                'gallery' => [
                    'bnb/urban1.jpg',
                    'bnb/urban2.jpg',
                ],
            ],
        ];

        foreach ($sampleBnbs as $bnbData) {
            $bnb = BedAndBreakfast::create(array_merge($bnbData, [
                'provider_id' => $providers->random()->id,
                'slug' => Str::slug($bnbData['name']).'-'.Str::random(8),
            ]));
            $bnb->accommodation()->update(['destination_id' => $destinations->random()->id]);

            // Attach amenities
            $bnb->amenities()->attach(
                $amenities->random(rand(3, 6))->pluck('id')->toArray()
            );

            // Add reviews
            $numberOfReviews = rand(2, 5);
            for ($i = 0; $i < $numberOfReviews; $i++) {
                $bnb->reviews()->create([
                    'guest_id' => $guests->random()->id,
                    'rating' => rand(3, 5),
                    'comment' => Arr::random([
                        'Amazing hospitality and great breakfast!',
                        'Very cozy and comfortable stay.',
                        'Loved the location and friendly host.',
                        'Would definitely come back again.',
                    ]),
                ]);
            }

            $bnb->forceFill(['is_active' => true, 'is_verified' => true])->save();
            $accommodation = $bnb->accommodation()->firstOrFail();
            $accommodation->update([
                'status' => AccommodationStatus::Published,
                'verification_status' => VerificationStatus::Approved,
                'verified_at' => now(),
                'published_at' => now(),
            ]);
            $accommodation->verificationHistory()->create([
                'status' => VerificationStatus::Approved->value,
                'reason' => 'Seeded verified sample listing.',
            ]);
        }

        // Also create some randomized BnBs for variety
        BedAndBreakfast::factory(10)
            ->recycle($providers)
            ->recycle($destinations)
            ->create()
            ->each(function (BedAndBreakfast $bnb) use ($amenities, $destinations, $guests) {
                $bnb->accommodation()->update(['destination_id' => $destinations->random()->id]);
                $bnb->amenities()->attach(
                    $amenities->random(rand(2, 5))->pluck('id')->toArray()
                );

                $bnb->reviews()->createMany(
                    collect(range(1, rand(1, 3)))->map(fn () => [
                        'guest_id' => $guests->random()->id,
                        'rating' => rand(3, 5),
                        'comment' => 'This is a nice B&B for a short stay.',
                    ])
                );

                $bnb->forceFill(['is_active' => true, 'is_verified' => true])->save();
                $accommodation = $bnb->accommodation()->firstOrFail();
                $accommodation->update([
                    'status' => AccommodationStatus::Published,
                    'verification_status' => VerificationStatus::Approved,
                    'verified_at' => now(),
                    'published_at' => now(),
                ]);
                $accommodation->verificationHistory()->create([
                    'status' => VerificationStatus::Approved->value,
                    'reason' => 'Seeded verified sample listing.',
                ]);
            });
    }
}
