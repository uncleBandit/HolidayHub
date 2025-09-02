<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $hotels = Hotel::with('rooms')->get();

        if ($users->isEmpty() || $hotels->isEmpty()) {
            $this->command->warn('No users or hotels found. Please seed users and hotels first.');
            return;
        }

        foreach ($users as $user) {
            // Each user gets 1–5 bookings
            $bookingCount = rand(1, 5);

            for ($i = 0; $i < $bookingCount; $i++) {
                $hotel = $hotels->random();

                // Skip hotels with no rooms
                if ($hotel->rooms->isEmpty()) {
                    continue;
                }

                $room = $hotel->rooms->random();

                $checkIn  = Carbon::today()->addDays(rand(1, 60));
                $checkOut = (clone $checkIn)->addDays(rand(1, 10));
                $nights   = $checkIn->diffInDays($checkOut);

                $pricePerNight = $room->price_per_night ?? rand(50, 300);
                $totalAmount   = $pricePerNight * $nights;

                Booking::factory()->create([
                    'user_id'         => $user->id,
                    'hotel_id'        => $hotel->id,
                    'room_id'         => $room->id,
                    'check_in_date'   => $checkIn,
                    'check_out_date'  => $checkOut,
                    'guests_adults'   => rand(1, 3),
                    'guests_children' => rand(0, 2),
                    'price_per_night' => $pricePerNight,
                    'total_amount'    => $totalAmount,
                    'status'          => ['confirmed', 'pending', 'cancelled'][rand(0, 2)],
                ]);
            }
        }

        $this->command->info('Bookings seeded successfully!');
    }
}
