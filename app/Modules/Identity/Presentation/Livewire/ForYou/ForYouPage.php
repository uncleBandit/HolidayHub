<?php

namespace App\Modules\Identity\Presentation\Livewire\ForYou;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Destinations\Presentation\Livewire\Discover;
use App\Modules\Identity\Application\Services\ForYouFeed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Where travellers land after signing in.
 *
 * A personal feed mixing reels from the social feed, stays, experiences and
 * destinations, ranked by {@see ForYouFeed} against what the traveller has
 * saved, booked and where they live. The next trip rides along in the header.
 */
#[Layout('layouts.discovery')]
class ForYouPage extends Component
{
    private const PAGE = 12;

    #[Url(as: 'feed', except: 'for-you')]
    public string $filter = 'for-you';

    public int $limit = self::PAGE;

    public function setFilter(string $filter): void
    {
        if (array_key_exists($filter, ForYouFeed::FILTERS)) {
            $this->filter = $filter;
            $this->limit = self::PAGE;
        }
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE;
    }

    public function render()
    {
        $user = Auth::user();
        $feed = new ForYouFeed($user);
        $items = $feed->items($this->filter);
        $signals = $feed->signals();

        return view('livewire.for-you.for-you-page', [
            'user' => $user,
            'firstName' => Str::of($user->name)->before(' ')->toString(),
            'signals' => $signals,
            'tuning' => $this->tuning($signals),
            'filters' => ForYouFeed::FILTERS,
            'items' => $items->take($this->limit)->values(),
            'hasMore' => $items->count() > $this->limit,
            'trending' => $this->filter === 'for-you'
                ? $feed->items('trending')->where('type', '!=', 'reel')->take(5)->values()
                : collect(),
            'moods' => collect(Discover::MOODS)->map(fn ($mood, $key) => [
                'label' => $mood['label'],
                'line' => $mood['line'],
                'image' => 'https://images.unsplash.com/photo-'.$mood['places'][0]['photo'].'?auto=format&fit=crop&w=240&h=240&q=80',
                'url' => route('discover', ['mood' => $key]),
            ]),
            'nextTrip' => $this->nextTrip(),
        ])->title('For you · HolidayHub');
    }

    /**
     * One honest line about what the feed is tuned to.
     */
    private function tuning(array $signals): string
    {
        $parts = array_filter([
            $signals['city'] || $signals['country_label']
                ? 'what’s near '.collect([$signals['city'], $signals['country_label']])->filter()->implode(', ')
                : null,
            $signals['loved'] ? 'places like '.collect($signals['loved'])->take(2)->implode(' and ') : null,
            'what travellers are loving this week',
        ]);

        return 'Tuned to '.collect($parts)->join(', ', count($parts) > 2 ? ', and ' : ' and ').'.';
    }

    private function nextTrip(): ?array
    {
        $booking = Auth::user()->guest?->bookings()
            ->with(['bookable', 'destination', 'activitySession.activity'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('check_out_date', '>=', today())
            ->orderBy('check_in_date')
            ->first();

        if (! $booking instanceof Booking) {
            return null;
        }

        $item = $booking->activitySession?->activity ?? $booking->bookable;

        if ($item !== null && method_exists($item, 'bookable') && $item->bookable !== null && empty($item->cover_image)) {
            $item = $item->bookable;
        }

        $checkIn = $booking->check_in_date;
        $checkOut = $booking->check_out_date;
        $image = collect([$item?->cover_image ?? null, $item?->thumbnail ?? null, ((array) ($item?->gallery ?? []))[0] ?? null])
            ->first(fn ($image) => is_string($image) && filled($image) && ! Str::contains($image, 'placeholder'));

        return [
            'name' => $item?->name ?? 'Your stay',
            'place' => $booking->destination?->name ?? ($item?->city ?? null),
            'image' => $image
                ? (Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/')))
                : 'https://images.unsplash.com/photo-1651860282131-e3257674ccd1?auto=format&fit=crop&w=600&q=80',
            'dates' => $checkIn
                ? ($checkOut && ! $checkIn->isSameDay($checkOut) ? $checkIn->format('j M').' – '.$checkOut->format('j M Y') : $checkIn->format('D, j M Y'))
                : 'Dates to be confirmed',
            'countdown' => $checkIn?->isFuture() ? (int) ceil(now()->diffInDays($checkIn)) : null,
            'guests' => (int) $booking->guests_adults + (int) $booking->guests_children,
            'code' => $booking->confirmation_code ?: 'HH-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'total' => 'KSh '.number_format((float) $booking->total_amount),
            'url' => route('bookings-show', $booking),
        ];
    }
}
