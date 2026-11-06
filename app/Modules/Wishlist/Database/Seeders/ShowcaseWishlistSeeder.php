<?php

namespace App\Modules\Wishlist\Database\Seeders;

use App\Modules\Booking\Database\Seeders\ShowcaseBookingSeeder;
use App\Modules\Identity\Database\Seeders\ShowcaseAccountSeeder;
use App\Modules\Wishlist\Domain\Models\Wishlist;
use Illuminate\Database\Seeder;

/**
 * Saved stays for every verified traveller: 3 or 4 published showcase stays,
 * chosen so they do not overlap the stays that traveller has booked.
 *
 * Idempotent: rows are unique on (user, wishlistable) and created with
 * firstOrCreate.
 */
class ShowcaseWishlistSeeder extends Seeder
{
    private const NOTES = [
        'Anniversary trip idea',
        'Ask about the family rate',
        'For the December break',
        'Looks perfect for a long weekend',
    ];

    private const PRIORITIES = ['high', 'medium', 'medium', 'low'];

    public function run(): void
    {
        $stays = ShowcaseBookingSeeder::orderedStays();
        if ($stays->isEmpty()) {
            $this->command?->warn('No published showcase stays; run ShowcaseStaySeeder first.');

            return;
        }

        $created = 0;
        $travellers = ShowcaseAccountSeeder::travellers();

        foreach ($travellers as $index => $user) {
            $count = 3 + $user->id % 2;

            for ($j = 0; $j < $count; $j++) {
                // Offsets 3..6 skip the three stays the booking seeder used.
                $stay = $stays[($index * 3 + 3 + $j) % $stays->count()];

                $wishlist = Wishlist::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'wishlistable_type' => $stay->bookable_type,
                        'wishlistable_id' => $stay->bookable_id,
                    ],
                    [
                        'priority' => self::PRIORITIES[$j],
                        'notes' => self::NOTES[($index + $j) % count(self::NOTES)],
                    ],
                );

                $created += $wishlist->wasRecentlyCreated ? 1 : 0;
            }
        }

        $this->command?->info(sprintf('Showcase wishlists: %d entries added for %d travellers.', $created, $travellers->count()));
    }
}
