<?php

namespace App\Modules\Booking\Presentation\Support;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Domain\Models\Booking;
use Illuminate\Support\Str;

/**
 * Display-only view model for a booking: the traveller-facing "trip" the
 * booking views render (photo, place, dates, money, status tone).
 *
 * It only reads relations the calling component has already loaded, so it is
 * safe under preventLazyLoading for paginated lists.
 */
final class TripPresenter
{
    private const PHOTO = 'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=%d&q=84';

    private const FALLBACK_PHOTOS = [
        'hotel' => '1574236170880-fbbca132d83d',
        'villa' => '1561501900-3701fa6a0864',
        'bed_and_breakfast' => '1708119063168-4785d1359824',
        'activity' => '1585402969048-86f38f8bfc2e',
        'default' => '1651860282131-e3257674ccd1',
    ];

    public static function make(Booking $booking, int $width = 900): array
    {
        $item = $booking->relationLoaded('bookable') ? $booking->bookable : null;
        $type = (string) $booking->bookable_type;
        $checkIn = $booking->check_in_date;
        $checkOut = $booking->check_out_date;
        $nights = $checkIn && $checkOut ? max(0, (int) $checkIn->diffInDays($checkOut)) : 0;
        $adults = (int) $booking->guests_adults;
        $children = (int) $booking->guests_children;
        $guests = $adults + $children;
        $status = (string) $booking->status;
        $currency = self::currency($booking->currency);
        $total = (float) ($booking->total_amount ?? $booking->getAttribute('total_price') ?? 0);
        $destination = $booking->relationLoaded('destination') ? $booking->destination : null;

        $policies = $item?->policies ?? null;
        if (is_string($policies)) {
            $policies = json_decode($policies, true);
        }
        $policies = is_array($policies) ? $policies : [];

        return [
            'name' => $item?->name ?? 'Your trip',
            'kind' => match ($type) {
                'hotel' => 'Hotel',
                'villa' => 'Villa',
                'bed_and_breakfast' => 'B&B',
                'activity', 'experience' => 'Experience',
                default => 'Stay',
            },
            'place' => $destination?->name
                ?? collect([$item?->city ?? null, $item?->country ?? null])->filter()->implode(', ')
                ?: null,
            'city' => $destination?->name ?? ($item?->city ?? null),
            'image' => self::image($item, $type, $width),
            'url' => self::url($item),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'dates' => $checkIn
                ? ($checkOut && ! $checkIn->isSameDay($checkOut)
                    ? ($checkIn->isSameMonth($checkOut)
                        ? $checkIn->format('j').'–'.$checkOut->format('j M Y')
                        : $checkIn->format('j M').' – '.$checkOut->format('j M Y'))
                    : $checkIn->format('D, j M Y'))
                : 'Dates to be confirmed',
            'nights' => $nights,
            'adults' => $adults,
            'children' => $children,
            'guests' => $guests,
            'guests_label' => $guests > 0 ? $guests.' '.Str::plural('guest', $guests) : 'Guests TBC',
            'code' => $booking->confirmation_code ?: 'HH-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'currency' => $currency,
            'total' => $total,
            'total_label' => $currency.' '.number_format($total),
            'per_night' => (float) $booking->price_per_night,
            'status' => $status,
            'status_label' => Str::of($status ?: 'pending')->replace('_', ' ')->ucfirst()->toString(),
            'status_class' => match ($status) {
                'confirmed' => 'bg-[#E3EFE7] text-forest',
                'pending' => 'bg-[#F8E9C9] text-[#7A5310]',
                'cancelled' => 'bg-[#F8DFD6] text-clay-dark',
                default => 'bg-sand text-[#4A514B]',
            },
            'bucket' => match (true) {
                $status === 'cancelled' => 'cancelled',
                in_array($status, ['completed', 'checked_out'], true),
                $checkOut !== null && $checkOut->isBefore(today()) => 'past',
                default => 'upcoming',
            },
            'countdown' => $checkIn?->isFuture() && $status !== 'cancelled' ? (int) ceil(now()->diffInDays($checkIn)) : null,
            'policy' => $policies['cancellation'] ?? null,
            'check_in_time' => $policies['check_in'] ?? null,
            'check_out_time' => $policies['check_out'] ?? null,
        ];
    }

    public static function currency(?string $code): string
    {
        return match (strtoupper((string) $code)) {
            '', 'KES', 'KSH' => 'KSh',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => strtoupper((string) $code),
        };
    }

    /**
     * First real photo of the booked item, skipping seeded placeholder hosts.
     */
    public static function image(mixed $item, string $type = 'default', int $width = 900): string
    {
        $candidates = [
            $item?->cover_image ?? null,
            $item?->thumbnail ?? null,
            ((array) ($item?->gallery ?? []))[0] ?? null,
        ];

        foreach ($candidates as $image) {
            if (! is_string($image) || blank($image) || Str::contains($image, ['placeholder', 'placehold', 'picsum', 'lorempixel'])) {
                continue;
            }

            return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/'));
        }

        return sprintf(self::PHOTO, self::FALLBACK_PHOTOS[$type] ?? self::FALLBACK_PHOTOS['default'], $width);
    }

    public static function url(mixed $item): ?string
    {
        return match (true) {
            $item instanceof Hotel => route('hotel-show', $item),
            $item instanceof Villa => route('villa.show', $item),
            $item instanceof BedAndBreakfast => route('bedandbreakfast.show', $item),
            $item instanceof Activity => route('activity.show', $item),
            default => null,
        };
    }
}
