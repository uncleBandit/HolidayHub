<?php

namespace App\Modules\Booking\Application\Services;

use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Domain\Models\Booking;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Works out what a guest gets back when they cancel, from the cancellation
 * policy the host chose for the listing (the wording the guest saw when they
 * booked), so the refund always matches what was promised.
 *
 * Hosts pick one of the wizard presets (HotelCreate::CANCELLATION), and older
 * listings hold free-text wording, so the wording is read rather than keyed:
 *
 *   "Free cancellation up to 24 hours before check-in."            → 100% until 24h before
 *   "Free cancellation up to 5 days before arrival; 50% charged
 *    after that."                                                   → 100% until 5 days, then 50%
 *   "Free cancellation up to 7 days before arrival; 50% refund up
 *    to 48 hours before."                                           → 100% until 7 days, 50% until 48h
 *   "Free cancellation within 48 hours of booking if made at least
 *    14 days ahead; non-refundable after."                          → 100% in the grace window, else 0%
 *
 * A listing without a policy gets the platform default, set on the admin
 * Platform settings page (free cancellation up to 48 hours before the start
 * until an admin changes it).
 *
 * A package (journey) follows the strictest policy among the stays and
 * experiences in it, because every one of those providers is bound by it.
 */
class CancellationTerms
{
    public const DEFAULT_TEXT = 'Free cancellation up to 48 hours before the start.';

    /** The platform default (Platform settings), for listings without a policy. */
    public function defaultText(): string
    {
        return (string) (app(\App\Modules\Administration\Application\Services\PlatformSettings::class)
            ->get('bookings.default_cancellation') ?: self::DEFAULT_TEXT);
    }

    /**
     * The refund a guest would get for cancelling now.
     *
     * @return array{
     *   text: string, start: CarbonImmutable, started: bool,
     *   percent: int, amount: float, currency: string,
     *   paid: bool, free_until: ?CarbonImmutable, summary: string
     * }
     */
    public function quote(Booking $booking, ?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? now());
        $start = $this->startOf($booking);
        $paid = $booking->payment_status === 'paid';

        [$text, $percent, $freeUntil] = $booking->bookable_type === 'package'
            ? $this->strictestForPackage($booking, $start, $now)
            : $this->evaluate($this->policyText($booking->bookable), $booking, $start, $now);

        $started = $now->greaterThanOrEqualTo($start);
        if ($started) {
            $percent = 0;
        }

        $currency = Str::upper((string) ($booking->currency ?: 'KES'));
        $amount = $paid ? round((float) $booking->total_amount * $percent / 100, 2) : 0.0;

        return [
            'text' => $text,
            'start' => $start,
            'started' => $started,
            'percent' => $percent,
            'amount' => $amount,
            'currency' => $currency,
            'paid' => $paid,
            'free_until' => $freeUntil,
            'summary' => $this->summary($paid, $percent, $amount, $currency, $freeUntil, $now),
        ];
    }

    /**
     * The cancellation wording for a listing, as its host wrote or chose it.
     */
    public function policyText(mixed $listing): string
    {
        if ($listing instanceof RoomType) {
            $listing = $listing->hotel;
        }

        $text = match (true) {
            $listing instanceof Activity => data_get($listing->getAttribute('attributes'), 'cancellation_policy'),
            $listing !== null => data_get($listing->policies ?? null, 'cancellation'),
            default => null,
        };

        return filled($text) ? trim((string) $text) : $this->defaultText();
    }

    /**
     * Read the wording into a refund for cancelling at $now.
     *
     * @return array{0: string, 1: int, 2: ?CarbonImmutable} text, refund %, free cancellation deadline
     */
    public function evaluate(string $text, Booking $booking, CarbonImmutable $start, CarbonImmutable $now): array
    {
        $lower = Str::lower($text);

        if (Str::contains($lower, 'non-refundable') && ! Str::contains($lower, 'free cancellation')) {
            return [$text, 0, null];
        }

        // "Free cancellation within 48 hours of booking if made at least 14 days ahead"
        if (preg_match('/within (\d+) (hour|day)s? of booking(?: if made at least (\d+) (hour|day)s? ahead)?/', $lower, $m)) {
            $booked = CarbonImmutable::instance($booking->created_at ?? $now);
            $grace = $booked->add($this->interval((int) $m[1], $m[2]));
            $aheadEnough = ! isset($m[3]) || $booked->add($this->interval((int) $m[3], $m[4]))->lessThanOrEqualTo($start);
            $freeUntil = $aheadEnough ? $grace->min($start) : null;

            return [$text, $freeUntil && $now->lessThan($freeUntil) ? 100 : 0, $freeUntil];
        }

        // "Free cancellation up to N hours/days before …"
        $freeUntil = null;
        if (preg_match('/free cancellation up to (\d+) (hour|day)s? before/', $lower, $m)) {
            $freeUntil = $start->sub($this->interval((int) $m[1], $m[2]));
        }

        if ($freeUntil && $now->lessThan($freeUntil)) {
            return [$text, 100, $freeUntil];
        }

        // "50% refund up to 48 hours before" — a partial refund until a later cut-off.
        if (preg_match('/(\d+)% refund(?:ed)? up to (\d+) (hour|day)s? before/', $lower, $m)) {
            $partialUntil = $start->sub($this->interval((int) $m[2], $m[3]));

            return [$text, $now->lessThan($partialUntil) ? (int) $m[1] : 0, $freeUntil];
        }

        // "50% charged after that" — after the free window half is kept.
        if (preg_match('/(\d+)% charged after/', $lower, $m)) {
            return [$text, max(0, 100 - (int) $m[1]), $freeUntil];
        }

        return [$text, 0, $freeUntil];
    }

    /** When the stay or experience begins. */
    public function startOf(Booking $booking): CarbonImmutable
    {
        $session = $booking->relationLoaded('activitySession')
            ? $booking->activitySession
            : $booking->activitySession()->first();

        if ($session?->starts_at) {
            return CarbonImmutable::instance($session->starts_at);
        }

        // Stays begin at check-in on the arrival day.
        return CarbonImmutable::parse($booking->check_in_date)->setTime(14, 0);
    }

    /**
     * The journey's terms: each component booking's own policy, keeping the
     * one that refunds least right now.
     *
     * @return array{0: string, 1: int, 2: ?CarbonImmutable}
     */
    private function strictestForPackage(Booking $parent, CarbonImmutable $start, CarbonImmutable $now): array
    {
        $children = Booking::query()
            ->where('package_booking_id', $parent->id)
            ->with(['bookable', 'activitySession'])
            ->get();

        if ($children->isEmpty()) {
            return $this->evaluate($this->defaultText(), $parent, $start, $now);
        }

        $strictest = $children
            ->map(function (Booking $child) use ($now) {
                $text = $this->policyText($child->bookable);
                [, $percent, $freeUntil] = $this->evaluate($text, $child, $this->startOf($child), $now);

                return compact('text', 'percent', 'freeUntil');
            })
            ->sortBy([['percent', 'asc'], [fn ($a, $b) => ($a['freeUntil']?->timestamp ?? 0) <=> ($b['freeUntil']?->timestamp ?? 0)]])
            ->first();

        return [
            'This journey follows the strictest policy among its stays and experiences: '.$strictest['text'],
            $strictest['percent'],
            $strictest['freeUntil'],
        ];
    }

    private function summary(bool $paid, int $percent, float $amount, string $currency, ?CarbonImmutable $freeUntil, CarbonImmutable $now): string
    {
        if (! $paid) {
            return 'Nothing has been charged for this booking, so there is nothing to refund.';
        }

        $money = \App\Shared\Support\Currencies::format($amount, $currency, 2);

        return match (true) {
            $percent >= 100 => "You get a full refund of {$money}"
                .($freeUntil && $freeUntil->greaterThan($now) ? ' if you cancel before '.$freeUntil->format('j M Y, H:i').'.' : '.'),
            $percent > 0 => "You get {$percent}% back: {$money}.",
            default => 'This booking is no longer refundable, so nothing will be refunded.',
        };
    }

    private function interval(int $count, string $unit): \DateInterval
    {
        return $unit === 'day'
            ? new \DateInterval('P'.$count.'D')
            : new \DateInterval('PT'.$count.'H');
    }
}
