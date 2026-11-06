<?php

namespace App\Modules\Providers\Presentation\Livewire\Provider;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Catalog\Presentation\Livewire\Forms\OfferForm;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Providers\Domain\Models\Provider;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The host studio: how a provider's stays and experiences are doing.
 *
 * Every figure comes from the bookings on the provider's own listings (stays
 * via their bookable, experiences via their sessions). The previous version
 * counted bookings through offers only, so a host without offers saw nothing,
 * and it crashed on a rooms.price_per_night column that does not exist.
 *
 * Hosts can answer pending requests and manage their offers. Every action
 * checks the record belongs to the signed-in provider. Amenities are a shared
 * catalogue, so they are no longer editable from here.
 */
#[Layout('layouts.discovery')]
class ProviderDashboard extends Component
{
    use WithFileUploads;

    private const EARNING = ['confirmed', 'checked_in', 'checked_out'];

    private const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1651860282131-e3257674ccd1?auto=format&fit=crop&w=900&q=80';

    public OfferForm $offerForm;

    public bool $showOfferModal = false;

    /**
     * The listing an offer is for, as "type:id" (e.g. "hotel:3").
     */
    public string $offerListing = '';

    public function openOfferModal(?int $offerId = null): void
    {
        $offer = $offerId ? $this->provider()->offers()->findOrFail($offerId) : null;

        $this->resetValidation();
        $this->offerForm->setOffer($offer);
        $this->offerListing = $offer ? $offer->offerable_type.':'.$offer->offerable_id : '';
        $this->showOfferModal = true;
    }

    public function saveOffer(): void
    {
        $listings = $this->listingOptions();

        $this->validate(['offerListing' => ['required', Rule::in(array_keys($listings))]], [
            'offerListing.required' => 'Choose which listing this offer is for.',
            'offerListing.in' => 'Choose one of your own listings.',
        ]);

        // An existing offer can only be edited by the provider who owns it.
        if ($this->offerForm->offer && $this->offerForm->offer->provider_id !== $this->provider()->id) {
            abort(403);
        }

        [$type, $id] = explode(':', $this->offerListing);
        $this->offerForm->offerable_type = $type;
        $this->offerForm->offerable_id = (int) $id;
        $this->offerForm->destination_id ??= $listings[$this->offerListing]['destination_id'];

        $this->offerForm->save();
        $this->showOfferModal = false;
        $this->dispatch('success', 'Offer saved.');
    }

    public function deleteOffer(int $offerId): void
    {
        $this->provider()->offers()->findOrFail($offerId)->delete();
        $this->dispatch('success', 'Offer deleted.');
    }

    /**
     * Accept or decline a booking request on one of this provider's listings.
     */
    public function updateBookingStatus(int $bookingId, string $status): void
    {
        abort_unless(in_array($status, ['confirmed', 'cancelled'], true), 422);

        [$stays, $activities] = $this->listings();
        $booking = $this->bookingsQuery($stays, $activities)->whereKey($bookingId)->firstOrFail();

        if ($booking->status !== 'pending') {
            return;
        }

        $booking->update([
            'status' => $status,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
        ]);

        $this->dispatch('success', $status === 'confirmed' ? 'Booking accepted.' : 'Booking declined.');
    }

    public function render()
    {
        $provider = $this->provider();
        [$stays, $activities] = $this->listings();

        $bookings = $this->bookingsQuery($stays, $activities)
            ->with(['guest', 'bookable', 'activitySession.activity'])
            ->get();

        $byListing = $bookings->groupBy(fn (Booking $booking) => $this->listingKeyFor($booking));

        return view('livewire.provider.provider-dashboard', [
            'provider' => $provider,
            'greeting' => match (true) {
                now()->hour < 12 => 'Good morning',
                now()->hour < 18 => 'Good afternoon',
                default => 'Good evening',
            },
            'kpis' => $this->kpis($bookings, $stays, $activities),
            'months' => $this->revenueByMonth($bookings),
            'requests' => $bookings->where('status', 'pending')
                ->filter(fn (Booking $booking) => $booking->check_in_date?->gte(today()))
                ->sortBy('check_in_date')
                ->take(6)
                ->map(fn (Booking $booking) => $this->mapBooking($booking))
                ->values(),
            'arrivals' => $bookings->whereIn('status', ['confirmed', 'checked_in'])
                ->filter(fn (Booking $booking) => $booking->check_out_date?->gte(today()))
                ->sortBy('check_in_date')
                ->take(6)
                ->map(fn (Booking $booking) => $this->mapBooking($booking))
                ->values(),
            'recent' => $bookings->sortByDesc('created_at')->take(8)->map(fn (Booking $booking) => $this->mapBooking($booking))->values(),
            'listings' => $this->mapListings($stays, $activities, $byListing),
            'reels' => $provider->mediaPosts()->count(),
            'offers' => $provider->offers()->latest()->get(),
            'listingOptions' => $this->listingOptions(),
            'destinations' => Destination::orderBy('name')->pluck('name', 'id'),
        ])->title('Host studio · HolidayHub');
    }

    // ── Data ─────────────────────────────────────────────────────────────

    private function provider(): Provider
    {
        $provider = Auth::user()->provider;
        abort_if($provider === null, 403, 'This account has no provider profile.');

        return $provider;
    }

    /**
     * @return array{0: Collection<int, Accommodation>, 1: Collection<int, Activity>}
     */
    private function listings(): array
    {
        $provider = $this->provider();

        return [
            $provider->accommodations()->with(['bookable', 'destination'])->get(),
            $provider->activities()->with('destination')->get(),
        ];
    }

    /**
     * Bookings on any of the provider's stays or experiences.
     */
    private function bookingsQuery(Collection $stays, Collection $activities): Builder
    {
        return Booking::query()->where(function (Builder $query) use ($stays, $activities) {
            $query->whereRaw('1 = 0');

            $stays->groupBy('bookable_type')->each(fn (Collection $group, string $type) => $query->orWhere(
                fn (Builder $where) => $where->where('bookable_type', $type)->whereIn('bookable_id', $group->pluck('bookable_id'))
            ));

            if ($activities->isNotEmpty()) {
                $query->orWhereHas('activitySession', fn (Builder $session) => $session->whereIn('activity_id', $activities->pluck('id')));
            }
        });
    }

    private function kpis(Collection $bookings, Collection $stays, Collection $activities): array
    {
        $earning = $bookings->whereIn('status', self::EARNING);
        $window = fn (int $from, int $to) => $earning
            ->filter(fn (Booking $b) => $b->created_at->between(now()->subDays($from), now()->subDays($to)))
            ->sum('total_amount');

        $thisMonth = $window(30, 0);
        $lastMonth = $window(60, 30);

        $upcoming = $bookings->whereIn('status', ['confirmed', 'pending'])
            ->filter(fn (Booking $b) => $b->check_in_date?->between(today(), today()->addDays(30)));

        $ratings = $stays->map(fn (Accommodation $s) => (float) ($s->bookable->avg_rating ?? $s->avg_rating ?? 0))
            ->merge($activities->map(fn (Activity $a) => (float) $a->rating))
            ->filter();

        $listings = $stays->count() + $activities->count();
        $live = $stays->filter(fn (Accommodation $s) => $this->statusOf($s) === 'published')->count()
            + $activities->filter(fn (Activity $a) => $this->statusOf($a) === 'published')->count();

        return [
            'revenue' => $thisMonth,
            'revenue_change' => $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100) : null,
            'arrivals' => $upcoming->count(),
            'nights' => $upcoming->sum(fn (Booking $b) => $b->check_in_date->diffInDays($b->check_out_date)),
            'pending' => $bookings->where('status', 'pending')->filter(fn (Booking $b) => $b->check_in_date?->gte(today()))->count(),
            'rating' => $ratings->isNotEmpty() ? round($ratings->avg(), 1) : null,
            'live' => $live,
            'listings' => $listings,
        ];
    }

    /**
     * Earnings by arrival month for the last six months, current month last.
     */
    private function revenueByMonth(Collection $bookings): Collection
    {
        $earning = $bookings->whereIn('status', self::EARNING);

        return collect(range(5, 0))->map(function (int $back) use ($earning) {
            $month = now()->startOfMonth()->subMonths($back);

            return [
                'label' => $month->format('M'),
                'long' => $month->format('F Y'),
                'total' => (float) $earning->filter(fn (Booking $b) => $b->check_in_date?->isSameMonth($month))->sum('total_amount'),
                'count' => $earning->filter(fn (Booking $b) => $b->check_in_date?->isSameMonth($month))->count(),
                'current' => $back === 0,
            ];
        });
    }

    private function mapBooking(Booking $booking): array
    {
        $item = $booking->activitySession?->activity ?? $booking->bookable;
        $nights = $booking->check_in_date && $booking->check_out_date ? $booking->check_in_date->diffInDays($booking->check_out_date) : 0;
        $guestName = trim(($booking->guest?->first_name ?? '').' '.($booking->guest?->last_name ?? '')) ?: 'Guest';

        return [
            'id' => $booking->id,
            'guest' => $guestName,
            'initials' => Str::upper(collect(explode(' ', $guestName))->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('')),
            'from' => collect([$booking->guest?->city, $booking->guest?->country])->filter()->implode(', '),
            'listing' => $item->name ?? 'Listing',
            'dates' => $this->dateRange($booking->check_in_date, $booking->check_out_date),
            'when' => $this->relativeArrival($booking->check_in_date),
            'nights' => $nights,
            'party' => (int) $booking->guests_adults + (int) $booking->guests_children,
            'total' => 'KSh '.number_format((float) $booking->total_amount),
            'status' => $booking->status,
            'status_label' => Str::of($booking->status)->replace('_', ' ')->title()->toString(),
            'code' => $booking->confirmation_code,
            'booked' => $booking->created_at?->diffForHumans(),
        ];
    }

    private function mapListings(Collection $stays, Collection $activities, Collection $byListing): Collection
    {
        $stayCards = $stays->map(function (Accommodation $stay) use ($byListing) {
            $bookable = $stay->bookable;
            $status = $this->statusOf($stay);

            return [
                'key' => 'stay-'.$stay->id,
                'name' => $bookable?->name ?: $stay->name,
                'kind' => match ($stay->bookable_type) {
                    'villa' => 'Villa',
                    'bed_and_breakfast' => 'B&B',
                    default => 'Hotel',
                },
                'place' => $stay->destination?->name ?: $bookable?->city,
                'image' => $this->imageFor([$bookable?->cover_image, ((array) ($bookable?->gallery ?? []))[0] ?? null, $stay->cover_image]),
                'status' => $status,
                'rating' => (float) ($bookable->avg_rating ?? $stay->avg_rating ?? 0),
                'price' => (float) ($bookable->price_per_night ?? $bookable->avg_price_per_night ?? $stay->avg_price_per_night ?? 0),
                'unit' => 'night',
                'bookings' => ($byListing->get($stay->bookable_type.':'.$stay->bookable_id) ?? collect())->whereIn('status', self::EARNING)->count(),
                'manage' => $bookable ? match ($stay->bookable_type) {
                    'hotel' => route('provider.hotel-manage', $bookable),
                    'villa' => route('provider.villa-manage', $bookable),
                    'bed_and_breakfast' => route('provider.bedandbreakfast-manage', $bookable),
                    default => null,
                } : null,
                'in_setup' => $bookable?->setup_step !== null,
                // Why the review team sent it back or took it down.
                'note' => in_array($status, ['rejected', 'suspended'], true) ? $stay->suspension_reason : null,
                'url' => $status === 'published' && $bookable ? match ($stay->bookable_type) {
                    'hotel' => route('hotel-show', $bookable),
                    'villa' => route('villa.show', $bookable),
                    'bed_and_breakfast' => route('bedandbreakfast.show', $bookable),
                    default => null,
                } : null,
            ];
        });

        $activityCards = $activities->map(function (Activity $activity) use ($byListing) {
            $status = $this->statusOf($activity);

            return [
                'key' => 'activity-'.$activity->id,
                'name' => $activity->name,
                'kind' => 'Experience',
                'place' => $activity->destination?->name,
                'image' => $this->imageFor([$activity->thumbnail, ((array) ($activity->gallery ?? []))[0] ?? null]),
                'status' => $status,
                'rating' => (float) $activity->rating,
                'price' => (float) $activity->base_price,
                'unit' => 'person',
                'bookings' => ($byListing->get('activity:'.$activity->id) ?? collect())->whereIn('status', self::EARNING)->count(),
                'manage' => route('provider.activity-manage', $activity),
                'in_setup' => $activity->setup_step !== null,
                'note' => match ($status) {
                    'rejected' => $activity->rejection_reason,
                    'suspended' => $activity->suspension_reason,
                    default => null,
                },
                'url' => $status === 'published' ? route('activity.show', $activity) : null,
            ];
        });

        return $stayCards->concat($activityCards)->values();
    }

    /**
     * The provider's listings an offer can be attached to, keyed "type:id".
     */
    private function listingOptions(): array
    {
        [$stays, $activities] = $this->listings();

        return $stays->mapWithKeys(fn (Accommodation $stay) => [
            $stay->bookable_type.':'.$stay->bookable_id => [
                'label' => ($stay->bookable?->name ?: $stay->name),
                'destination_id' => $stay->destination_id,
            ],
        ])->merge($activities->mapWithKeys(fn (Activity $activity) => [
            'activity:'.$activity->id => ['label' => $activity->name, 'destination_id' => $activity->destination_id],
        ]))->all();
    }

    private function listingKeyFor(Booking $booking): string
    {
        return $booking->activitySession
            ? 'activity:'.$booking->activitySession->activity_id
            : $booking->bookable_type.':'.$booking->bookable_id;
    }

    private function statusOf($model): string
    {
        $status = $model->status instanceof \BackedEnum ? $model->status->value : (string) $model->status;
        $verification = $model->verification_status instanceof \BackedEnum ? $model->verification_status->value : (string) $model->verification_status;

        return match (true) {
            filled($model->suspended_at ?? null) || $status === 'suspended' => 'suspended',
            $status === 'rejected' => 'rejected',
            $status === 'published' && in_array($verification, ['approved', ''], true) => 'published',
            in_array($verification, ['pending', 'in_review'], true) || $status === 'pending_review' => 'review',
            default => 'draft',
        };
    }

    private function imageFor(array $candidates): string
    {
        foreach ($candidates as $image) {
            if (is_string($image) && filled($image) && ! Str::contains($image, ['placeholder', 'placehold', 'picsum'])) {
                return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/'));
            }
        }

        return self::FALLBACK_IMAGE;
    }

    private function dateRange(?Carbon $from, ?Carbon $to): string
    {
        if (! $from) {
            return 'Dates to be confirmed';
        }

        if (! $to || $from->isSameDay($to)) {
            return $from->format('D j M');
        }

        return $from->isSameMonth($to) ? $from->format('j').'–'.$to->format('j M') : $from->format('j M').' – '.$to->format('j M');
    }

    private function relativeArrival(?Carbon $date): string
    {
        return match (true) {
            $date === null => '',
            $date->isToday() => 'Arrives today',
            $date->isTomorrow() => 'Arrives tomorrow',
            $date->isPast() => 'In residence',
            default => 'In '.(int) today()->diffInDays($date).' days',
        };
    }
}
