<?php

namespace App\Modules\Packages\Application\Services;

use App\Modules\Activities\Application\Services\ActivitySessionBookingService;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Booking\Application\Services\BookingManager;
use App\Modules\Booking\Domain\Events\BookingCreated;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Infrastructure\Gateways\CoveredByPackageGateway;
use App\Modules\Payments\Domain\Contracts\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Books a whole package, all or nothing.
 *
 * In one database transaction it creates the guest's package booking
 * (bookable = the package, in the package currency, with the agent's margin
 * as agent_commission), then books every stay and experience through its own
 * booking service (BookingManager for stays, ActivitySessionBookingService
 * for experiences), so each provider gets an ordinary booking in its own
 * currency, linked by package_booking_id. If any part can't be booked, the
 * whole transaction rolls back and nothing is created.
 *
 * The guest pays once: the component bookings are made with a gateway that
 * doesn't charge (CoveredByPackageGateway), the package booking is charged
 * with the real one, and the components then take its status.
 */
class PackageBooker
{
    public function __construct(
        private readonly PackagePricing $pricing,
        private readonly PaymentGateway $paymentGateway,
    ) {}

    public function book(Package $package, User $user, string $start, int $guests, ?string $idempotencyKey = null): Booking
    {
        if (! $package->isPublished()) {
            throw ValidationException::withMessages(['package' => 'This journey isn’t taking bookings at the moment.']);
        }
        $user->loadMissing('guest');
        if (! $user->guest) {
            throw ValidationException::withMessages(['package' => 'Complete your guest profile before booking.']);
        }

        $idempotencyKey ??= (string) Str::uuid();
        if ($existing = Booking::where('idempotency_key', $idempotencyKey)->first()) {
            if ((int) $existing->guest_id !== (int) $user->guest->id) {
                throw ValidationException::withMessages(['package' => 'This booking key is already in use.']);
            }

            return $existing;
        }

        try {
            return DB::transaction(function () use ($package, $user, $start, $guests, $idempotencyKey): Booking {
                // Quote inside the transaction, right before booking, so the
                // prices recorded are the live ones.
                $quote = $this->pricing->quote($package->fresh(), $start, $guests);

                if (! $quote['available']) {
                    throw ValidationException::withMessages(['package' => $this->whyUnavailable($quote)]);
                }

                $parent = Booking::create([
                    'guest_id' => $user->guest->id,
                    'bookable_type' => $package->getMorphClass(),
                    'bookable_id' => $package->id,
                    'destination_id' => $package->destination_id,
                    'check_in_date' => $quote['start'],
                    'check_out_date' => $quote['end'],
                    'guests_adults' => $quote['guests'],
                    'guests_children' => 0,
                    'total_amount' => $quote['total'],
                    'agent_commission' => $quote['margin'],
                    'currency' => $quote['currency'],
                    'status' => 'pending',
                    'idempotency_key' => $idempotencyKey,
                    'confirmation_code' => strtoupper(Str::random(10)),
                ]);

                foreach (collect($quote['lines'])->where('listing', true) as $line) {
                    try {
                        $child = $this->bookComponent($line, $user, $quote['guests']);
                    } catch (ValidationException $e) {
                        // A component's own service refused it: roll everything back.
                        throw new \DomainException($line['title'].': '.Str::lcfirst((string) collect($e->errors())->flatten()->first()), 0, $e);
                    }
                    $child->forceFill(['package_booking_id' => $parent->id])->save();
                }

                // One charge for the whole journey; the components follow it.
                $this->paymentGateway->charge($parent);
                $parent->refresh();
                Booking::where('package_booking_id', $parent->id)->update(['status' => $parent->status]);

                event(new BookingCreated($parent));

                return $parent;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('Package booking rolled back', ['package_id' => $package->id, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'package' => 'We couldn’t book every part of this journey ('.$this->plain($e).'), so nothing was booked and you haven’t been charged. Try another date.',
            ]);
        }
    }

    /** Book one stay or experience line through its own booking service. */
    private function bookComponent(array $line, User $user, int $guests): Booking
    {
        $gateway = new CoveredByPackageGateway;
        // The package booking carries the guest's idempotency key; each
        // component gets its own (the column holds a UUID).
        $key = (string) Str::uuid();

        if ($line['kind'] === 'experience') {
            $session = ActivitySession::query()->with('activity')->findOrFail($line['session_id']);
            $activity = $session->activity;

            if (! $activity instanceof Activity) {
                throw new \DomainException('the experience is no longer listed');
            }

            return app()->make(ActivitySessionBookingService::class, ['paymentGateway' => $gateway])
                ->book($activity, $session, $user, $guests, $key);
        }

        return app()->make(BookingManager::class, ['paymentGateway' => $gateway])->create([
            'bookable_type' => $line['bookable_type'],
            'bookable_id' => $line['bookable_id'],
            'check_in' => $line['check_in'],
            'check_out' => $line['check_out'],
            'guests' => $guests,
        ], $user->id, $key);
    }

    private function whyUnavailable(array $quote): string
    {
        if ($quote['errors'] !== []) {
            return $quote['errors'][0];
        }

        $line = collect($quote['lines'])->firstWhere('available', false);

        return $line
            ? 'Day '.$line['day'].', '.$line['title'].': '.Str::lcfirst(rtrim((string) $line['reason'], '.')).'. Try another start date.'
            : 'This journey can’t be booked on that date. Try another.';
    }

    /** A reason fit for a guest: the booking services' own messages, never SQL. */
    private function plain(Throwable $e): string
    {
        $message = match (true) {
            $e instanceof ValidationException => collect($e->errors())->flatten()->first(),
            $e instanceof \DomainException, $e instanceof \RuntimeException && ! $e instanceof \Illuminate\Database\QueryException => $e->getMessage(),
            default => 'one of them couldn’t be confirmed',
        };

        return Str::lcfirst(rtrim(Str::limit((string) $message, 120), '.'));
    }
}
