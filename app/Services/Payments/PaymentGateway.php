<?php

namespace App\Services\Payments;

use App\Models\Booking;
use JsonSerializable;

interface PaymentGateway
{
    /**
     * Authorize and capture payment for a booking.
     *
     * @param Booking $booking The booking being paid for
     * @param array $options Extra metadata (currency, return_url, customer_info, etc.)
     * @return PaymentResult
     */
    public function charge(Booking $booking, array $options = []): PaymentResult;

    /**
     * Refund a booking payment (full or partial).
     *
     * @param Booking $booking
     * @param float|null $amount If null, refund full amount
     * @param array $options Extra metadata (reason, reference)
     * @return PaymentResult
     */
    public function refund(Booking $booking, ?float $amount = null, array $options = []): PaymentResult;

    /**
     * Verify or sync payment status with provider (for async flows).
     *
     * @param Booking $booking
     * @return PaymentResult
     */
    public function verify(Booking $booking): PaymentResult;

    /**
     * Handle webhook callback from provider.
     *
     * @param array $payload
     * @return PaymentResult
     */
    public function handleWebhook(array $payload): PaymentResult;
}
