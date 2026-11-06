<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Modules\Accommodation\Database\Seeders\ShowcaseStaySeeder;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Database\Seeders\ShowcaseAccountSeeder;
use Illuminate\Database\Seeder;

/**
 * Trips for every verified traveller (the demo account included).
 *
 * Each traveller gets one upcoming confirmed stay 10–40 days out, one past
 * checked-out stay and one cancelled stay, all on published showcase stays.
 * Money is KES: total = nights × nightly price + a 5% service fee, with the
 * nightly price taken from the listing (cheapest room type for hotels).
 *
 * Idempotent: bookings are keyed on a deterministic confirmation code
 * (HH-SC-<user id>-UPC|PST|CXL). Existing bookings are left alone, except an
 * "upcoming" booking whose check-in has drifted inside 10 days, which is
 * rolled forward so the dashboard always has a future trip.
 */
class ShowcaseBookingSeeder extends Seeder
{
    public const SERVICE_FEE_RATE = 0.05;

    public function run(): void
    {
        $stays = self::orderedStays();
        if ($stays->isEmpty()) {
            $this->command?->warn('No published showcase stays; run ShowcaseStaySeeder first.');

            return;
        }

        $created = 0;
        $travellers = ShowcaseAccountSeeder::travellers();

        foreach ($travellers as $index => $user) {
            $guest = $user->guest()->first();
            if (! $guest) {
                continue;
            }

            $plan = [
                'UPC' => [
                    'stay' => $stays[($index * 3) % $stays->count()],
                    'check_in' => today()->addDays(10 + ($user->id * 7) % 31),
                    'nights' => 3 + $user->id % 3,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'payment_method' => 'mpesa',
                    'cancelled_at' => null,
                ],
                'PST' => [
                    'stay' => $stays[($index * 3 + 1) % $stays->count()],
                    'check_in' => today()->subDays(35 + ($user->id * 11) % 60),
                    'nights' => 2 + $user->id % 3,
                    'status' => 'checked_out',
                    'payment_status' => 'paid',
                    'payment_method' => 'credit_card',
                    'cancelled_at' => null,
                ],
                'CXL' => [
                    'stay' => $stays[($index * 3 + 2) % $stays->count()],
                    'check_in' => today()->addDays(55 + ($user->id * 5) % 30),
                    'nights' => 2 + ($user->id + 1) % 3,
                    'status' => 'cancelled',
                    'payment_status' => 'refunded',
                    'payment_method' => 'mpesa',
                    'cancelled_at' => now()->subDays(4),
                ],
            ];

            foreach ($plan as $suffix => $trip) {
                $code = sprintf('HH-SC-%d-%s', $user->id, $suffix);
                $booking = Booking::query()->firstOrNew(['confirmation_code' => $code]);

                $stale = $booking->exists
                    && $suffix === 'UPC'
                    && $booking->check_in_date->lt(today()->addDays(10));

                if ($booking->exists && ! $stale) {
                    continue;
                }

                $booking->fill($this->attributes($guest->id, $trip, $code));
                $booking->save();
                $created++;
            }
        }

        $this->command?->info(sprintf('Showcase bookings: %d created or refreshed for %d travellers.', $created, $travellers->count()));
    }

    /**
     * Published showcase stays in the seeder's declared order.
     *
     * @return \Illuminate\Support\Collection<int, Accommodation>
     */
    public static function orderedStays()
    {
        $bySlug = ShowcaseStaySeeder::accommodations();

        return collect(ShowcaseStaySeeder::staySlugs())
            ->map(fn (array $pair) => $bySlug->get($pair[1]))
            ->filter()
            ->values();
    }

    public static function nightlyPrice(Accommodation $stay): float
    {
        $property = $stay->bookable;

        return (float) ($property->price_per_night ?? $property->avg_price_per_night ?? $stay->avg_price_per_night);
    }

    private function attributes(int $guestId, array $trip, string $code): array
    {
        /** @var Accommodation $stay */
        $stay = $trip['stay'];
        $price = self::nightlyPrice($stay);
        $subtotal = $price * $trip['nights'];
        $fee = round($subtotal * self::SERVICE_FEE_RATE);

        return [
            'guest_id' => $guestId,
            'bookable_type' => $stay->bookable_type,
            'bookable_id' => $stay->bookable_id,
            'destination_id' => $stay->destination_id,
            'check_in_date' => $trip['check_in']->toDateString(),
            'check_out_date' => $trip['check_in']->copy()->addDays($trip['nights'])->toDateString(),
            'guests_adults' => 2,
            'guests_children' => 0,
            'price_per_night' => $price,
            'total_amount' => $subtotal + $fee,
            'currency' => 'KES',
            'status' => $trip['status'],
            'payment_status' => $trip['payment_status'],
            'payment_method' => $trip['payment_method'],
            'cancelled_at' => $trip['cancelled_at'],
            'idempotency_key' => 'showcase-'.strtolower($code),
        ];
    }
}
