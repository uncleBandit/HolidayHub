<?php

namespace App\Modules\Payments\Infrastructure\Gateways;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Payments\Domain\ValueObjects\PaymentResult;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    public function charge(Booking $booking, array $options = []): PaymentResult
    {
        try {
            $paymentIntent = $this->client->paymentIntents->create([
                'amount' => (int) ($booking->total * 100), // cents
                'currency' => $options['currency'] ?? 'usd',
                'metadata' => [
                    'booking_id' => $booking->id,
                ],
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return PaymentResult::pending($paymentIntent->id, 'Payment initiated', $paymentIntent->toArray());
        } catch (\Exception $e) {
            return PaymentResult::failure($e->getMessage(), 'STRIPE_ERROR');
        }
    }

    public function refund(Booking $booking, ?float $amount = null, array $options = []): PaymentResult
    {
        try {
            $refund = $this->client->refunds->create([
                'payment_intent' => $booking->transaction_id,
                'amount' => $amount ? (int) ($amount * 100) : null,
            ]);

            return PaymentResult::refunded($refund->id, 'Refund processed', $refund->toArray());
        } catch (\Exception $e) {
            return PaymentResult::failure($e->getMessage(), 'STRIPE_REFUND_ERROR');
        }
    }

    public function verify(Booking $booking): PaymentResult
    {
        try {
            $intent = $this->client->paymentIntents->retrieve($booking->transaction_id);

            return match ($intent->status) {
                'succeeded' => PaymentResult::success($intent->id, 'Payment succeeded', $intent->toArray()),
                'processing', 'requires_action' => PaymentResult::pending($intent->id, 'Payment pending', $intent->toArray()),
                default => PaymentResult::failure("Payment status: {$intent->status}", 'STRIPE_VERIFY_ERROR', $intent->toArray()),
            };
        } catch (\Exception $e) {
            return PaymentResult::failure($e->getMessage(), 'STRIPE_VERIFY_EXCEPTION');
        }
    }

    public function handleWebhook(array $payload): PaymentResult
    {
        // Simplified example
        if (($payload['type'] ?? null) === 'payment_intent.succeeded') {
            $intent = $payload['data']['object'];

            return PaymentResult::success($intent['id'], 'Webhook: Payment succeeded', $payload);
        }

        return PaymentResult::failure('Unhandled webhook event', 'UNHANDLED_WEBHOOK', $payload);
    }
}
