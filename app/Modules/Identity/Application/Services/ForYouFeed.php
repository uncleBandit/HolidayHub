<?php

namespace App\Modules\Identity\Application\Services;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Destinations\Presentation\Livewire\Discover;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds the signed-in "For you" feed.
 *
 * Every candidate (reels from the social feed, published stays, experiences,
 * destinations and editor's picks) is scored against the traveller's signals:
 * places they have saved or booked, where they live, what is trending and how
 * well it is rated. Each item carries the reason it was chosen so the feed can
 * say so. Editor's picks keep the feed full while the catalogue is thin.
 */
class ForYouFeed
{
    public const FILTERS = [
        'for-you' => ['label' => 'For you', 'icon' => 'star'],
        'trending' => ['label' => 'Trending', 'icon' => 'arrow-up-right'],
        'near' => ['label' => 'Near you', 'icon' => 'map'],
        'stays' => ['label' => 'Stays', 'icon' => 'bed'],
        'experiences' => ['label' => 'Experiences', 'icon' => 'compass'],
        'reels' => ['label' => 'Reels', 'icon' => 'play'],
    ];

    private const COUNTRY_CODES = ['ke' => 'kenya', 'jp' => 'japan', 'it' => 'italy', 'ma' => 'morocco', 'tz' => 'tanzania', 'ug' => 'uganda'];

    /**
     * Saved items and reel targets are hotels, villas or B&Bs whose destination
     * lives on the canonical accommodation row; load it up front.
     */
    private const STAY_RELATIONS = [
        Hotel::class => ['accommodation'],
        Villa::class => ['accommodation'],
        BedAndBreakfast::class => ['accommodation'],
    ];

    private const PHOTO ='https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=%d&q=84';

    private ?array $signals = null;

    public function __construct(private readonly User $user) {}

    /**
     * What the feed knows about the traveller, for the header copy.
     */
    public function signals(): array
    {
        if ($this->signals !== null) {
            return $this->signals;
        }

        $guest = $this->user->guest;
        $saved = $this->user->wishlists()
            ->with(['wishlistable' => fn ($morph) => $morph->morphWith(self::STAY_RELATIONS)])
            ->get()
            ->pluck('wishlistable')
            ->filter();
        $booked = $guest?->bookings()->pluck('destination_id') ?? collect();

        $destinationIds = $saved
            ->map(fn ($item) => $item->destination_id ?? $item->accommodation?->destination_id)
            ->merge($booked)
            ->filter()
            ->unique()
            ->values();

        return $this->signals = [
            'city' => $guest?->city,
            'country' => $this->normaliseCountry($guest?->country),
            'country_label' => $guest?->country ? Str::title($this->normaliseCountry($guest->country)) : null,
            'loved' => Destination::whereIn('id', $destinationIds)->pluck('name', 'id')->all(),
            'saved_count' => $saved->count(),
        ];
    }

    /**
     * The ranked feed for a filter.
     */
    public function items(string $filter): Collection
    {
        $items = Cache::remember('for-you.'.$this->user->id, 120, fn () => $this->candidates()->all());
        $items = collect($items);

        if ($filter !== 'for-you') {
            return $items->filter(fn ($item) => in_array($filter, $item['tags'], true))
                ->sortByDesc('score')
                ->values();
        }

        return $this->interleave($items->sortByDesc('score')->values());
    }

    private function candidates(): Collection
    {
        return collect()
            ->merge($this->reels())
            ->merge($this->stays())
            ->merge($this->experiences())
            ->merge($this->destinations())
            ->merge($this->picks());
    }

    /**
     * A reel roughly every fourth card, so the feed keeps moving.
     */
    private function interleave(Collection $items): Collection
    {
        [$reels, $cards] = $items->partition(fn ($item) => $item['type'] === 'reel');
        $reels = $reels->values();
        $cards = $cards->values();
        $feed = collect();

        while ($reels->isNotEmpty() || $cards->isNotEmpty()) {
            $takeReel = $reels->isNotEmpty() && ($cards->isEmpty() || $feed->count() % 4 === 1);
            $feed->push($takeReel ? $reels->shift() : $cards->shift());
        }

        return $feed;
    }

    // ── Sources ──────────────────────────────────────────────────────────

    private function reels(): Collection
    {
        $posts = MediaPost::publiclyPublished()
            ->with(['assets', 'provider', 'targetable' => fn ($morph) => $morph->morphWith(self::STAY_RELATIONS)])
            ->latest('published_at')
            ->take(16)
            ->get();

        if ($posts->isEmpty()) {
            return collect(config('stays.clips', []))->values()->take(8)->map(fn ($clip, $i) => $this->item([
                'key' => 'clip-'.$i,
                'type' => 'reel',
                'title' => Str::of($clip['title'])->before(' with ')->toString(),
                'sub' => 'HolidayHub stays',
                'video' => $clip['video'],
                'image' => $clip['poster'],
                'url' => route('stays.index').'?view=reels',
                'tags' => ['reels'],
                'reason' => ['icon' => 'play', 'label' => 'Stays in motion'],
                'score' => 12 - $i,
            ]));
        }

        return $posts->map(function (MediaPost $post) {
            $fresh = $post->published_at?->gt(now()->subDays(7));
            $target = $post->targetable;

            return $this->item([
                'key' => 'reel-'.$post->id,
                'type' => 'reel',
                'title' => $post->title ?: Str::limit((string) $post->caption, 48),
                'sub' => $post->provider?->company_name ?: 'From the feed',
                'video' => $this->assetUrl($post, MediaAssetType::Original),
                'image' => $this->assetUrl($post, MediaAssetType::Thumbnail),
                'url' => $target ? $this->urlFor($target) : route('stays.index').'?view=reels',
                'tags' => array_values(array_filter(['reels', $fresh ? 'trending' : null])),
                'reason' => $fresh
                    ? ['icon' => 'arrow-up-right', 'label' => 'Trending on the feed']
                    : ['icon' => 'play', 'label' => 'From the feed'],
                'score' => 55 + ($fresh ? 25 : 0) - (int) $post->published_at?->diffInDays(now()),
                'destination_id' => $target?->destination_id ?? $target?->accommodation?->destination_id,
            ]);
        });
    }

    private function stays(): Collection
    {
        return Accommodation::published()
            ->with(['bookable', 'destination'])
            ->orderByDesc('is_featured')
            ->latest()
            ->take(30)
            ->get()
            ->map(function (Accommodation $stay) {
                $bookable = $stay->bookable;
                $rating = (float) ($bookable->avg_rating ?? $stay->avg_rating ?? 0);
                $price = (float) ($bookable->price_per_night ?? $bookable->avg_price_per_night ?? $stay->avg_price_per_night ?? 0);

                return $this->item([
                    'key' => 'stay-'.$stay->id,
                    'type' => 'stay',
                    'kicker' => match ($stay->bookable_type) {
                        'villa' => 'Villa',
                        'bed_and_breakfast' => 'B&B',
                        default => 'Stay',
                    },
                    'title' => $bookable?->name ?: $stay->name,
                    'sub' => $stay->destination?->name ?: collect([$bookable?->city, $stay->country])->filter()->implode(', '),
                    'image' => $this->image([$bookable?->cover_image, $bookable?->gallery[0] ?? null, $stay->cover_image]),
                    'url' => $this->urlFor($stay),
                    'price' => $price > 0 ? 'KSh '.number_format($price).' / night' : null,
                    'rating' => $rating,
                    'tags' => ['stays'],
                    'trending' => (bool) ($bookable?->is_featured ?? $stay->is_featured) || $rating >= 4.7,
                    'score' => 30 + $rating * 4,
                    'destination_id' => $stay->destination_id,
                    'city' => $bookable?->city,
                    'country' => $stay->destination?->country ?? $stay->country,
                ]);
            });
    }

    private function experiences(): Collection
    {
        return Activity::published()
            ->with('destination')
            ->orderByDesc('is_featured')
            ->orderByDesc('bookings_count')
            ->take(30)
            ->get()
            ->map(function (Activity $activity) {
                $hours = $activity->duration_minutes ? round($activity->duration_minutes / 60, 1) : null;

                return $this->item([
                    'key' => 'activity-'.$activity->id,
                    'type' => 'experience',
                    'kicker' => 'Experience',
                    'title' => $activity->name,
                    'sub' => collect([$activity->destination?->name, $hours ? Str::replaceLast('.0', '', (string) $hours).' hrs' : null])->filter()->implode(' · '),
                    'image' => $this->image([$activity->thumbnail, ((array) $activity->gallery)[0] ?? null]),
                    'url' => route('activity.show', $activity),
                    'price' => $activity->base_price
                        ? 'From '.(in_array(strtoupper((string) $activity->currency), ['', 'KES', 'KSH'], true) ? 'KSh' : strtoupper($activity->currency)).' '.number_format((float) $activity->base_price)
                        : null,
                    'rating' => (float) $activity->rating,
                    'tags' => ['experiences'],
                    'trending' => (bool) $activity->is_featured || (int) $activity->bookings_count >= 10,
                    'score' => 32 + (float) $activity->rating * 4 + min(20, (int) $activity->bookings_count),
                    'destination_id' => $activity->destination_id,
                    'country' => $activity->destination?->country,
                ]);
            });
    }

    private function destinations(): Collection
    {
        return Destination::query()
            ->orderByDesc('is_featured')
            ->orderByDesc('popularity_score')
            ->take(30)
            ->get()
            ->map(function (Destination $destination) {
                $image = $this->image([$destination->image_url, $destination->thumbnail, ((array) $destination->gallery)[0] ?? null], null);

                if ($image === null) {
                    return null;
                }

                return $this->item([
                    'key' => 'destination-'.$destination->id,
                    'type' => 'destination',
                    'kicker' => 'Destination',
                    'title' => $destination->name,
                    'sub' => $destination->tagline ?: collect([$destination->city, $destination->country])->filter()->implode(' · '),
                    'image' => $image,
                    'url' => route('destination.show', $destination),
                    'tags' => ['destinations'],
                    'trending' => (bool) $destination->is_featured || (float) $destination->popularity_score >= 80,
                    'score' => 26 + min(20, (float) $destination->popularity_score / 5),
                    'destination_id' => $destination->id,
                    'city' => $destination->city,
                    'country' => $destination->country,
                ]);
            })
            ->filter();
    }

    /**
     * Curated places from the Discover moods.
     */
    private function picks(): Collection
    {
        return collect(Discover::MOODS)->flatMap(fn ($mood, $key) => collect($mood['places'])->map(fn ($place, $i) => $this->item([
            'key' => 'pick-'.$key.'-'.$i,
            'type' => 'pick',
            'kicker' => $mood['label'],
            'title' => $place['name'],
            'sub' => $place['line'],
            'image' => sprintf(self::PHOTO, $place['photo'], 1000),
            'url' => route('discover', ['mood' => $key]),
            'tags' => ['picks'],
            'reason' => ['icon' => 'leaf', 'label' => 'Editor’s pick'],
            'score' => 10 - $i,
            'country' => $place['country'],
        ])));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Apply the traveller's signals: boosts, tags and the reason label.
     */
    private function item(array $item): array
    {
        $signals = $this->signals();
        $tags = $item['tags'];
        $reason = $item['reason'] ?? null;
        $score = $item['score'];

        $loved = $signals['loved'][$item['destination_id'] ?? 0] ?? null;
        $near = ($signals['city'] && isset($item['city']) && Str::lower($item['city']) === Str::lower($signals['city']))
            || ($signals['country'] && isset($item['country']) && $this->normaliseCountry($item['country']) === $signals['country']);

        if ($item['trending'] ?? false) {
            $tags[] = 'trending';
            $score += 20;
            $reason ??= ['icon' => 'arrow-up-right', 'label' => 'Trending this week'];
        }

        if ($near) {
            $tags[] = 'near';
            $score += 30;
            $reason = $item['type'] === 'pick' ? ['icon' => 'map', 'label' => 'Near you · Editor’s pick'] : ['icon' => 'map', 'label' => 'Near you'];
        }

        if ($loved) {
            $score += 45;
            $reason = ['icon' => 'heart', 'label' => 'Because you love '.$loved];
        }

        $reason ??= ($item['rating'] ?? 0) >= 4.5
            ? ['icon' => 'star', 'label' => 'Top rated']
            : ['icon' => 'compass', 'label' => 'New on HolidayHub'];

        return [
            'key' => $item['key'],
            'type' => $item['type'],
            'kicker' => $item['kicker'] ?? null,
            'title' => $item['title'],
            'sub' => $item['sub'] ?? null,
            'image' => $item['image'] ?? null,
            'video' => $item['video'] ?? null,
            'url' => $item['url'],
            'price' => $item['price'] ?? null,
            'rating' => ($item['rating'] ?? 0) > 0 ? number_format($item['rating'], 1) : null,
            'tags' => array_values(array_unique($tags)),
            'reason' => $reason,
            'score' => $score,
        ];
    }

    private function urlFor(Model $model): string
    {
        if ($model instanceof Activity) {
            return route('activity.show', $model);
        }

        $type = $model instanceof Accommodation ? $model->bookable_type : $model->getMorphClass();
        $bookable = $model instanceof Accommodation ? $model->bookable : $model;

        if ($bookable === null) {
            return route('stays.index');
        }

        return match ($type) {
            'hotel' => route('hotel-show', $bookable),
            'villa' => route('villa.show', $bookable),
            'bed_and_breakfast' => route('bedandbreakfast.show', $bookable),
            default => route('stays.index'),
        };
    }

    private function assetUrl(MediaPost $post, MediaAssetType $type): ?string
    {
        try {
            return $post->asset($type)?->url;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * First usable image, skipping seeded placeholder services.
     */
    private function image(array $candidates, ?string $fallback = 'https://images.unsplash.com/photo-1651860282131-e3257674ccd1?auto=format&fit=crop&w=1000&q=84'): ?string
    {
        foreach ($candidates as $image) {
            if (! is_string($image) || blank($image) || Str::contains($image, ['placeholder', 'placehold', 'picsum', 'lorempixel'])) {
                continue;
            }

            return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/'));
        }

        return $fallback;
    }

    private function normaliseCountry(?string $country): ?string
    {
        if (blank($country)) {
            return null;
        }

        $country = Str::lower(trim($country));

        return self::COUNTRY_CODES[$country] ?? $country;
    }
}
