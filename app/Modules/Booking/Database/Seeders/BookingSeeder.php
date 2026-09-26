<?php

namespace App\Modules\Booking\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\Guest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $guests = Guest::with('user')->get();

        $accommodations = collect()
            ->merge(Hotel::with('rooms')->get())
            ->merge(Villa::all())
            ->merge(BedAndBreakfast::all());

        if ($guests->isEmpty() || $accommodations->isEmpty()) {
            $this->command->warn('No guests or accommodations found.');

            return;
        }

        foreach ($guests as $guest) {
            $bookingCount = rand(1, 5);

            for ($i = 0; $i < $bookingCount; $i++) {
                $accommodation = $accommodations->random();

                // If Hotel, pick a room
                $room = $accommodation instanceof Hotel && $accommodation->rooms->isNotEmpty()
                    ? $accommodation->rooms->random()
                    : null;

                $checkIn = Carbon::today()->addDays(rand(1, 60));
                $checkOut = (clone $checkIn)->addDays(rand(1, 10));
                $nights = $checkIn->diffInDays($checkOut);

                $pricePerNight = $room->price_per_night ?? ($accommodation->price_per_night ?? rand(50, 300));
                $totalAmount = $pricePerNight * $nights;

                Booking::factory()
                    ->for($guest, 'guest')
                    ->for($accommodation, 'bookable') // polymorphic relation
                    ->create([
                        'check_in_date' => $checkIn,
                        'check_out_date' => $checkOut,
                        'guests_adults' => rand(1, 4),
                        'guests_children' => rand(0, 3),
                        'price_per_night' => $pricePerNight,
                        'total_amount' => $totalAmount,
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'currency' => 'USD',
                    ]);
            }
        }

        $this->command->info('✅ Polymorphic bookings seeded successfully for all guests!');
    }
}
