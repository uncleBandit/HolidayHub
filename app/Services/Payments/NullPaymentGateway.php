<?php

namespace App\Services\Payments;

use App\Models\Booking;

class NullPaymentGateway implements PaymentGateway
{
    public function charge(Booking $booking, array $options = []): PaymentResult
    {
        // Auto-confirm booking in test mode
        $booking->update(['status' => 'confirmed']);

        return PaymentResult::success(
            transactionId: 'null-charge-' . $booking->id,
            message: 'Test mode: booking auto-confirmed',
            raw: ['mode' => 'test']
        );
    }

    public function refund(Booking $booking, ?float $amount = null, array $options = []): PaymentResult
    {
        return PaymentResult::refunded(
            transactionId: 'null-refund-' . $booking->id,
            message: 'Test mode: refund simulated',
            raw: ['mode' => 'test', 'amount' => $amount]
        );
    }

    public function verify(Booking $booking): PaymentResult
    {
        return PaymentResult::success(
            transactionId: 'null-verify-' . $booking->id,
            message: 'Test mode: payment verified',
            raw: ['mode' => 'test']
        );
    }

    public function handleWebhook(array $payload): PaymentResult
    {
        return PaymentResult::success(
            transactionId: 'null-webhook',
            message: 'Test mode: webhook ignored',
            raw: $payload
        );
    }
}
