<?php

namespace App\Modules\Packages\Infrastructure\Gateways;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use App\Modules\Payments\Domain\ValueObjects\PaymentResult;

/**
 * The payment gateway the component bookings of a package are made with.
 *
 * A guest pays once, for the package booking. The stay and experience
 * bookings inside it go through their normal booking services (availability,
 * pricing, capacity), but must not be charged on their own: this gateway
 * leaves them pending, and PackageBooker gives them the package booking's
 * status once that single charge has gone through.
 */
final class CoveredByPackageGateway implements PaymentGateway
{
    public function charge(Booking $booking, array $options = []): PaymentResult
    {
        return PaymentResult::pending('package-component-'.$booking->id, 'Paid with the package booking.');
    }

    public function refund(Booking $booking, ?float $amount = null, array $options = []): PaymentResult
    {
        return PaymentResult::refunded('package-component-'.$booking->id, 'Refunded with the package booking.');
    }

    public function verify(Booking $booking): PaymentResult
    {
        return PaymentResult::pending('package-component-'.$booking->id, 'Paid with the package booking.');
    }

    public function handleWebhook(array $payload): PaymentResult
    {
        return PaymentResult::success('package-component', 'Nothing to do.');
    }
}
