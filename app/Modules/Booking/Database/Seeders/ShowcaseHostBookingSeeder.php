<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\Guest;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Host-side activity for the showcase: a steady stream of guests across every
 * published stay, so the provider dashboard has revenue, arrivals and requests
 * to show. Guests get unverified, unusable accounts (guests.user_id is required)
 * and are therefore never picked up as showcase travellers.
 *
 * Idempotent: bookings match on their confirmation code and are left alone
 * once they exist.
 */
class ShowcaseHostBookingSeeder extends Seeder
{
    private const GUESTS = [
        ['Amina', 'Odhiambo', 'Nairobi', 'Kenya'],
        ['Lukas', 'Becker', 'Munich', 'Germany'],
        ['Priya', 'Nair', 'Bengaluru', 'India'],
        ['James', 'Mwangi', 'Nakuru', 'Kenya'],
        ['Sofia', 'Rossi', 'Milan', 'Italy'],
        ['Kenji', 'Tanaka', 'Osaka', 'Japan'],
        ['Fatima', 'Hassan', 'Mombasa', 'Kenya'],
        ['Oliver', 'Grant', 'London', 'United Kingdom'],
    ];

    /**
     * Each stay's schedule, as [days from today, nights, status]. Past stays
     * spread over six months; a few sit ahead, including requests to answer.
     */
    private const PLAN = [
        [-160, 3, 'checked_out'],
        [-118, 2, 'checked_out'],
        [-84, 4, 'checked_out'],
        [-51, 3, 'checked_out'],
        [-22, 2, 'checked_out'],
        [-9, 3, 'cancelled'],
        [6, 3, 'confirmed'],
        [17, 4, 'pending'],
        [29, 2, 'confirmed'],
    ];

    public function run(): void
    {
        $stays = Accommodation::published()->with('bookable')->orderBy('id')->get();

        if ($stays->isEmpty()) {
            $this->command?->warn('No published stays; run ShowcaseStaySeeder first.');

            return;
        }

        $guests = $this->guests();
        $created = 0;

        foreach ($stays as $s => $stay) {
            $price = ShowcaseBookingSeeder::nightlyPrice($stay);

            foreach (self::PLAN as $p => [$offset, $nights, $status]) {
                // Not every stay is busy every month.
                if (($s + $p) % 4 === 3) {
                    continue;
                }

                $code = sprintf('HH-HOST-%d-%02d', $stay->id, $p);
                $booking = Booking::query()->firstOrNew(['confirmation_code' => $code]);

                if ($booking->exists) {
                    continue;
                }

                $checkIn = today()->addDays($offset + ($s % 5));
                $subtotal = $price * $nights;
                $guest = $guests[($s * 3 + $p) % $guests->count()];

                $booking->fill([
                    'guest_id' => $guest->id,
                    'bookable_type' => $stay->bookable_type,
                    'bookable_id' => $stay->bookable_id,
                    'destination_id' => $stay->destination_id,
                    'check_in_date' => $checkIn->toDateString(),
                    'check_out_date' => $checkIn->copy()->addDays($nights)->toDateString(),
                    'guests_adults' => 1 + ($s + $p) % 3,
                    'guests_children' => ($s + $p) % 5 === 0 ? 1 : 0,
                    'price_per_night' => $price,
                    'total_amount' => $subtotal + round($subtotal * ShowcaseBookingSeeder::SERVICE_FEE_RATE),
                    'currency' => 'KES',
                    'status' => $status,
                    'payment_status' => match ($status) {
                        'cancelled' => 'refunded',
                        'pending' => 'pending',
                        default => 'paid',
                    },
                    'payment_method' => $p % 2 === 0 ? 'mpesa' : 'credit_card',
                    'cancelled_at' => $status === 'cancelled' ? $checkIn->copy()->subDays(5) : null,
                ]);

                // Booked some weeks before arrival, never in the future.
                $booking->created_at = min(now(), $checkIn->copy()->subDays(14 + ($p * 3) % 20));
                $booking->save();
                $created++;
            }
        }

        $this->command?->info(sprintf('Showcase host bookings: %d created across %d stays.', $created, $stays->count()));
    }

    private function guests()
    {
        return collect(self::GUESTS)->map(function (array $person) {
            [$first, $last, $city, $country] = $person;
            $email = Str::lower($first.'.'.$last).'@guests.holidayhub.test';

            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $first.' '.$last, 'password' => Hash::make(Str::random(40))]
            );

            return Guest::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => $email,
                    'city' => $city,
                    'country' => $country,
                    'preferred_currency' => 'KES',
                ]
            );
        });
    }
}
