<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn  = $this->faker->dateTimeBetween('now', '+6 months');
        $checkOut = (clone $checkIn)->modify('+' . $this->faker->numberBetween(1, 14) . ' days');

        $guestsAdults   = $this->faker->numberBetween(1, 4);
        $guestsChildren = $this->faker->numberBetween(0, 3);
        $nights         = (int) $checkOut->diff($checkIn)->format('%a');
        $pricePerNight  = $this->faker->randomFloat(2, 50, 500);
        $totalAmount    = ($pricePerNight * $nights) * (1 + $this->faker->randomFloat(2, 0, 0.2)); // optional fees

        $paymentStatuses = ['pending', 'paid', 'failed', 'refunded'];
        $bookingStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
        $paymentMethods  = ['credit_card', 'paypal', 'mpesa', 'bank_transfer', 'other'];
        $currencyOptions = ['USD', 'EUR', 'GBP', 'KES'];

        return [
            'guest_id'          => Guest::factory(),
            'bookable_type'     => null, // will be set in seeder
            'bookable_id'       => null, // will be set in seeder
            'check_in_date'     => $checkIn,
            'check_out_date'    => $checkOut,
            'guests_adults'     => $guestsAdults,
            'guests_children'   => $guestsChildren,
            'price_per_night'   => $pricePerNight,
            'total_amount'      => $totalAmount,
            'currency'          => $this->faker->randomElement($currencyOptions),
            'payment_status'    => $this->faker->randomElement($paymentStatuses),
            'payment_method'    => $this->faker->randomElement($paymentMethods),
            'status'            => $this->faker->randomElement($bookingStatuses),
            'special_requests'  => $this->faker->optional(0.3)->paragraph(),
            'confirmation_code' => strtoupper(Str::random(8)),
            'cancelled_at'      => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];
    }

    /**
     * State for confirmed bookings.
     */
    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * State for cancelled bookings.
     */
    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * State for completed bookings.
     */
    public function completed(): static
    {
        return $this->state(fn() => [
            'status' => 'checked_out',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Attach a polymorphic bookable (Package, Hotel, Villa, BnB)
     */
    public function forBookable($bookable, string $relationship = 'bookable'): static
    {
        return $this->state(fn() => [
            'bookable_type' => get_class($bookable),
            'bookable_id'   => $bookable->id,
        ]);
    }
}
