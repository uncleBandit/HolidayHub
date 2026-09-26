<?php

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Payments\Domain\ValueObjects\PaymentResult;

/**
 * No-op gateway used in test/local mode.
 *
 * This file implements the shared PaymentGateway contract, but the interface and
 * the result value object were never imported and the class references resolved
 * to sibling names in the Gateways namespace that do not exist. It therefore
 * could not be loaded, so any attempt to resolve the PaymentGateway binding in
 * test mode died before it reached a single method. The missing imports are the
 * only fixes here.
 */
class NullPaymentGateway implements PaymentGateway
{
    public function charge(Booking $booking, array $options = []): PaymentResult
    {
        // Auto-confirm booking in test mode
        $booking->update(['status' => 'confirmed']);

        return PaymentResult::success(
            transactionId: 'null-charge-'.$booking->id,
            message: 'Test mode: booking auto-confirmed',
            raw: ['mode' => 'test']
        );
    }

    public function refund(Booking $booking, ?float $amount = null, array $options = []): PaymentResult
    {
        return PaymentResult::refunded(
            transactionId: 'null-refund-'.$booking->id,
            message: 'Test mode: refund simulated',
            raw: ['mode' => 'test', 'amount' => $amount]
        );
    }

    public function verify(Booking $booking): PaymentResult
    {
        return PaymentResult::success(
            transactionId: 'null-verify-'.$booking->id,
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
