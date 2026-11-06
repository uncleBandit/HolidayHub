<?php

namespace App\Modules\Packages\Application\Services;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Application\Services\ExperiencePricing;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Availability\Application\Services\AvailabilityEngine;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Domain\Models\PackageComponent;
use App\Modules\Pricing\Application\Services\PricingEngine;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * What a package costs on a given start date for a party of guests, and
 * whether every part of it can be booked then.
 *
 * Each stay and experience is priced live, exactly as its own booking service
 * prices it (stays: PricingEngine with seasonal rates, weekends, extra guests,
 * demand and offers; experiences: ExperiencePricing, per person or per private
 * group), in the listing's own currency; availability uses the same checks
 * those services make (AvailabilityEngine for stays, a bookable session with
 * room for the party for experiences). So the quote a guest sees is what the
 * component bookings record when they book.
 *
 * Listing prices in another currency are converted into the package currency
 * with the approximate table in config/packages.php (`rates`, per USD), and
 * the line is flagged as converted. Extras (transfers, free time, notes) use
 * the agent's own price. The agent's margin, a percent of the listings or a
 * fixed amount per booking, comes on top.
 */
class PackagePricing
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly AvailabilityEngine $availability,
        private readonly ExperiencePricing $experiences,
    ) {}

    /**
     * @return array{
     *   package_id: int, currency: string, start: string, end: string, guests: int,
     *   lines: array<int, array<string, mixed>>,
     *   listing_subtotal: float, extras_subtotal: float, subtotal: float,
     *   margin: float, total: float, per_person: float,
     *   available: bool, unavailable: int, converted: bool, errors: array<int, string>
     * }
     */
    public function quote(Package $package, CarbonInterface|string $start, int $guests): array
    {
        $start = Carbon::parse($start)->startOfDay();
        $guests = max(1, $guests);
        $currency = Str::upper($package->getCurrency());
        $this->ensureLoaded($package);

        $lines = $package->components
            ->map(fn (PackageComponent $component) => $this->line($component, $start, $guests, $currency))
            ->values();

        $listingLines = $lines->where('listing', true);
        $listingSubtotal = round((float) $listingLines->sum(fn ($line) => (float) ($line['amount'] ?? 0)), 2);
        $extrasSubtotal = round((float) $lines->where('listing', false)->sum(fn ($line) => (float) ($line['amount'] ?? 0)), 2);
        $margin = $this->margin($package, $listingSubtotal);
        $total = round($listingSubtotal + $extrasSubtotal + $margin, 2);

        $errors = [];
        if ($listingLines->isEmpty()) {
            $errors[] = 'This journey has no stays or experiences yet.';
        }
        if ($package->min_guests && $guests < (int) $package->min_guests) {
            $errors[] = 'This journey is for at least '.(int) $package->min_guests.' '.Str::plural('guest', (int) $package->min_guests).'.';
        }
        if ($package->max_guests && $guests > (int) $package->max_guests) {
            $errors[] = 'This journey takes up to '.(int) $package->max_guests.' '.Str::plural('guest', (int) $package->max_guests).'.';
        }
        if ($start->lt(today()->addDay())) {
            $errors[] = 'Choose a start date from tomorrow onwards.';
        }

        $unavailable = $lines->where('available', false)->count();

        return [
            'package_id' => (int) $package->id,
            'currency' => $currency,
            'start' => $start->toDateString(),
            'end' => $this->endDate($package, $start, $lines)->toDateString(),
            'guests' => $guests,
            'lines' => $lines->all(),
            'listing_subtotal' => $listingSubtotal,
            'extras_subtotal' => $extrasSubtotal,
            'subtotal' => round($listingSubtotal + $extrasSubtotal, 2),
            'margin' => $margin,
            'total' => $total,
            'per_person' => round($total / $guests, 2),
            'available' => $unavailable === 0 && $errors === [],
            'unavailable' => $unavailable,
            'converted' => $lines->contains('converted', true),
            'errors' => $errors,
        ];
    }

    /**
     * The next start dates on which the whole journey can be booked, soonest
     * first: candidate days are narrowed by the experiences' session days
     * before each one is fully quoted.
     *
     * @return array<int, array{date: string, total: float, per_person: float}>
     */
    public function nextDepartures(Package $package, int $guests, int $count = 4, int $horizonDays = 60, ?CarbonInterface $from = null): array
    {
        $this->ensureLoaded($package);
        $from = Carbon::instance($from ?? today()->addDays(2))->startOfDay();
        $until = $from->copy()->addDays($horizonDays);

        $candidates = collect();
        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            $candidates->push($date->toDateString());
        }

        // An experience on day N needs a session on start + N - 1.
        foreach ($package->components->where('kind', 'experience') as $component) {
            if (! $component->componentable instanceof Activity) {
                return [];
            }
            $offset = max(0, (int) $component->day - 1);
            $days = ActivitySession::query()
                ->where('activity_id', $component->componentable_id)
                ->when($component->activity_option_id, fn ($q) => $q->where('activity_option_id', $component->activity_option_id))
                ->bookable()
                ->whereBetween('starts_at', [$from->copy()->addDays($offset), $until->copy()->addDays($offset + 1)])
                ->pluck('starts_at')
                ->map(fn ($at) => Carbon::instance($at)->startOfDay()->subDays($offset)->toDateString())
                ->unique()
                ->all();
            $candidates = $candidates->intersect($days)->values();
        }

        $found = [];
        foreach ($candidates as $date) {
            $quote = $this->quote($package, $date, $guests);
            if ($quote['available']) {
                $found[] = ['date' => $date, 'total' => $quote['total'], 'per_person' => $quote['per_person']];
            }
            if (count($found) >= $count) {
                break;
            }
        }

        return $found;
    }

    /**
     * A representative quote for the builder and the cached "from" price: the
     * first bookable departure in the next two months, or a date a month out.
     */
    public function sampleQuote(Package $package, int $guests, ?string $date = null): array
    {
        if ($date === null) {
            $first = $this->nextDepartures($package, $guests, 1)[0]['date'] ?? null;
            $date = $first ?? today()->addDays((int) config('packages.default_lead_days', 30))->toDateString();
        }

        return $this->quote($package, $date, $guests);
    }

    /** Units of a currency per US dollar, from config/packages.php; null when unknown. */
    public function rate(?string $currency): ?float
    {
        $rate = config('packages.rates.'.Str::upper((string) $currency));

        return is_numeric($rate) && (float) $rate > 0 ? (float) $rate : null;
    }

    public function canConvert(?string $from, ?string $to): bool
    {
        return Str::upper((string) $from) === Str::upper((string) $to) || ($this->rate($from) && $this->rate($to));
    }

    /** An amount in one currency in another, via USD; null when either rate is unknown. */
    public function convert(float $amount, ?string $from, ?string $to): ?float
    {
        $from = Str::upper((string) $from);
        $to = Str::upper((string) $to);

        if ($from === $to) {
            return round($amount, 2);
        }

        $fromRate = $this->rate($from);
        $toRate = $this->rate($to);

        return $fromRate && $toRate ? round($amount / $fromRate * $toRate, 2) : null;
    }

    /** The margin on a listing subtotal, in the package currency. */
    public function margin(Package $package, float $listingSubtotal): float
    {
        $value = max(0, (float) $package->markup_value);

        return $package->markup_type === 'fixed'
            ? round($value, 2)
            : round($listingSubtotal * $value / 100, 2);
    }

    /** Relations a quote reads, for callers that want to eager-load them up front. */
    public function componentRelations(): array
    {
        return [
            'components' => fn ($query) => $query->with([
                'activityOption',
                'componentable' => fn (MorphTo $morph) => $morph->morphWith([
                    RoomType::class => ['hotel.accommodation'],
                    Villa::class => ['accommodation'],
                    BedAndBreakfast::class => ['accommodation'],
                    Activity::class => [],
                ]),
            ]),
        ];
    }

    /** Load the journey with its listings unless a caller already did. */
    public function ensureLoaded(Package $package): Package
    {
        $loaded = $package->relationLoaded('components')
            && $package->components->every(fn (PackageComponent $c) => $c->relationLoaded('componentable') && $c->relationLoaded('activityOption'));

        return $loaded ? $package : $package->load($this->componentRelations());
    }

    // ── Lines ────────────────────────────────────────────────────────────

    private function line(PackageComponent $component, Carbon $start, int $guests, string $currency): array
    {
        $date = $start->copy()->addDays(max(0, (int) $component->day - 1));
        $base = [
            'component_id' => (int) $component->id,
            'day' => (int) $component->day,
            'kind' => $component->kind,
            'listing' => $component->isListing(),
            'title' => (string) $component->title,
            'name' => (string) $component->title,
            'date' => $date->toDateString(),
            'check_in' => null,
            'check_out' => null,
            'nights' => null,
            'quantity' => max(1, (int) $component->quantity),
            'bookable_type' => null,
            'bookable_id' => null,
            'session_id' => null,
            'listing_amount' => null,
            'listing_currency' => null,
            'amount' => null,
            'converted' => false,
            'available' => true,
            'reason' => null,
        ];

        try {
            return match ($component->kind) {
                'stay' => $this->stayLine($component, $base, $date, $guests, $currency),
                'experience' => $this->experienceLine($component, $base, $date, $guests, $currency),
                default => $this->extraLine($component, $base, $currency),
            };
        } catch (Throwable $e) {
            report($e);

            return ['available' => false, 'reason' => 'We couldn’t price this part right now.'] + $base;
        }
    }

    private function stayLine(PackageComponent $component, array $line, Carbon $date, int $guests, string $currency): array
    {
        $stay = $component->componentable;
        $nights = max(1, (int) $component->nights);
        $checkOut = $date->copy()->addDays($nights);
        $line['check_in'] = $date->toDateString();
        $line['check_out'] = $checkOut->toDateString();
        $line['nights'] = $nights;

        if (! $stay instanceof RoomType && ! $stay instanceof Villa && ! $stay instanceof BedAndBreakfast) {
            return ['available' => false, 'reason' => 'This stay is no longer on HolidayHub.'] + $line;
        }

        $line['name'] = $stay instanceof RoomType ? trim(($stay->hotel?->name ?? 'Hotel').' · '.$stay->name) : (string) $stay->name;
        $line['title'] = $line['title'] ?: $line['name'];
        $line['bookable_type'] = $stay->getMorphClass();
        $line['bookable_id'] = (int) $stay->getKey();

        $accommodation = $stay instanceof RoomType ? $stay->hotel?->accommodation : $stay->accommodation;
        if (! $accommodation?->isPublished()) {
            return ['available' => false, 'reason' => 'This stay isn’t taking bookings at the moment.'] + $line;
        }

        $line = $this->priced($line, $this->pricing->calculateTotal($stay, $date, $checkOut, $guests), $stay->getCurrency(), $currency);

        if (! $this->availability->isAvailable($stay->getKey(), $stay->getMorphClass(), $date, $checkOut)) {
            $line['available'] = false;
            $line['reason'] = 'Fully booked for these nights.';
        }

        return $line;
    }

    private function experienceLine(PackageComponent $component, array $line, Carbon $date, int $guests, string $currency): array
    {
        $activity = $component->componentable;

        if (! $activity instanceof Activity) {
            return ['available' => false, 'reason' => 'This experience is no longer on HolidayHub.'] + $line;
        }

        $option = $component->activityOption;
        $line['name'] = $option ? $activity->name.' · '.$option->name : (string) $activity->name;
        $line['title'] = $line['title'] ?: $line['name'];
        $line['bookable_type'] = ($option ?? $activity)->getMorphClass();
        $line['bookable_id'] = (int) ($option ?? $activity)->getKey();
        $line['check_in'] = $date->toDateString();
        $line['check_out'] = $date->copy()->addDay()->toDateString();

        if (! $activity->isPublished()) {
            return ['available' => false, 'reason' => 'This experience isn’t taking bookings at the moment.'] + $line;
        }
        if ($component->activity_option_id && (! $option || ! $option->is_active || (int) $option->activity_id !== (int) $activity->id)) {
            return ['available' => false, 'reason' => 'The chosen option is no longer offered.'] + $line;
        }

        $session = $this->sessionFor($activity, $component->activity_option_id, $date, $guests);

        if (! $session) {
            // Still show what it would cost, from the listing price.
            $line = $this->priced($line, $this->experiences->total($option ?? $activity, $date, $guests), ($option ?? $activity)->getCurrency(), $currency);
            $line['available'] = false;
            $line['reason'] = 'No session with room for '.$guests.' on '.$date->format('D j M').'.';

            return $line;
        }

        // Priced exactly as ActivitySessionBookingService prices the booking.
        $bookable = $session->option ?? $activity;
        $sessionDay = Carbon::instance($session->starts_at)->startOfDay();
        $line['session_id'] = (int) $session->id;
        $line['starts_at'] = $session->starts_at->toIso8601String();
        $line['timezone'] = $session->timezone;
        $line['bookable_type'] = $bookable->getMorphClass();
        $line['bookable_id'] = (int) $bookable->getKey();

        $line = $this->priced($line, $this->experiences->total($bookable, $sessionDay, $guests), $bookable->getCurrency(), $currency);

        $maximum = $session->option?->max_participants ?? $activity->capacity;
        if ($maximum && $guests > (int) $maximum) {
            $line['available'] = false;
            $line['reason'] = 'Takes at most '.(int) $maximum.' per booking.';
        }

        return $line;
    }

    private function extraLine(PackageComponent $component, array $line, string $currency): array
    {
        $line['title'] = $line['title'] ?: (PackageComponent::KINDS[$component->kind] ?? 'Extra');
        $line['name'] = $line['title'];

        if ($component->kind === 'note' || $component->price === null) {
            $line['amount'] = 0.0;

            return $line;
        }

        $amount = (float) $component->price * max(1, (int) $component->quantity);

        return $this->priced($line, $amount, $component->currency ?: $currency, $currency);
    }

    /** Put a listing-currency amount on a line, converting it when needed. */
    private function priced(array $line, float $amount, ?string $listingCurrency, string $currency): array
    {
        $listingCurrency = Str::upper($listingCurrency ?: $currency);
        $line['listing_amount'] = round($amount, 2);
        $line['listing_currency'] = $listingCurrency;
        $line['converted'] = $listingCurrency !== $currency;
        $line['amount'] = $this->convert($amount, $listingCurrency, $currency);

        if ($line['amount'] === null) {
            $line['available'] = false;
            $line['reason'] = "We can’t convert {$listingCurrency} to {$currency} yet; choose another package currency.";
        }

        return $line;
    }

    /**
     * The session an experience component books on a date: open, past the
     * activity's minimum notice and with room for the whole party. With no
     * option chosen, sessions of the experience itself come first.
     */
    public function sessionFor(Activity $activity, ?int $optionId, CarbonInterface $date, int $guests): ?ActivitySession
    {
        $earliest = now()->addMinutes((int) $activity->minimum_notice_minutes);

        return ActivitySession::query()
            ->with('option')
            ->where('activity_id', $activity->id)
            ->when($optionId, fn ($q) => $q->where('activity_option_id', $optionId))
            ->bookable()
            ->whereBetween('starts_at', [Carbon::parse($date)->startOfDay(), Carbon::parse($date)->endOfDay()])
            ->where('starts_at', '>=', $earliest)
            ->orderByRaw('activity_option_id IS NULL DESC')
            ->orderBy('starts_at')
            ->get()
            ->first(fn (ActivitySession $session) => $session->availableCapacity() >= $guests);
    }

    private function endDate(Package $package, Carbon $start, Collection $lines): Carbon
    {
        $end = $start->copy()->addDays(max(1, (int) $package->duration_days - 1));
        $lastStay = $lines->pluck('check_out')->filter()->map(fn ($date) => Carbon::parse($date))->max();

        return $lastStay && $lastStay->gt($end) ? $lastStay : $end;
    }
}
