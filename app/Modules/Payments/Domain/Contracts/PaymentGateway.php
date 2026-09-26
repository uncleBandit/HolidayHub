<?php

namespace App\Modules\Payments\Domain\Contracts;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Payments\Domain\ValueObjects\PaymentResult;

interface PaymentGateway
{
    /**
     * Authorize and capture payment for a booking.
     *
     * @param  Booking  $booking  The booking being paid for
     * @param  array  $options  Extra metadata (currency, return_url, customer_info, etc.)
     */
    public function charge(Booking $booking, array $options = []): PaymentResult;

    /**
     * Refund a booking payment (full or partial).
     *
     * @param  float|null  $amount  If null, refund full amount
     * @param  array  $options  Extra metadata (reason, reference)
     */
    public function refund(Booking $booking, ?float $amount = null, array $options = []): PaymentResult;

    /**
     * Verify or sync payment status with provider (for async flows).
     */
    public function verify(Booking $booking): PaymentResult;

    /**
     * Handle webhook callback from provider.
     */
    public function handleWebhook(array $payload): PaymentResult;
}
