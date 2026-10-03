<?php

namespace App\Modules\Activities\Presentation\Livewire\Activity;

use App\Modules\Activities\Application\Services\ActivitySearchQuery;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Activities\Domain\Models\ActivityLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The experiences storefront.
 *
 * An activity is something a guest does, not somewhere they sleep, so this is
 * the stays section's sibling with a different set of questions behind it:
 * what kind of day, how long have I got, how many of us, and what does it cost
 * per person. The reel footage is picked from the experience's category so the
 * video always looks like the activity being sold.
 *
 * Two presentations of the same result set:
 *   grid  - a hover-to-play card grid, the browse view
 *   reels - a full-screen vertical feed, the discovery view
 */
#[Layout('layouts.discovery')]
class ExperiencesIndex extends Component
{
    use WithPagination;

    /**
     * Keep the grid and the feed in sync when flipping between them.
     */
    public int $perPage = 12;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type', except: 'all')]
    public string $type = 'all';

    #[Url(as: 'sort', except: 'recommended')]
    public string $sort = 'recommended';

    #[Url(as: 'view', except: 'grid')]
    public string $view = 'grid';

    #[Url(as: 'when', except: 'all')]
    public string $duration = 'all';

    #[Url(as: 'min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max', except: '')]
    public string $maxPrice = '';

    #[Url(as: 'place', except: '')]
    public string $destination = '';

    public const TYPES = [
        'all' => 'All experiences',
        'safari-wildlife' => 'Safari & wildlife',
        'ocean-water' => 'Ocean & water',
        'adventure-trekking' => 'Adventure',
        'culture-heritage' => 'Culture',
        'food-markets' => 'Food & markets',
        'wellness-retreats' => 'Wellness',
        'family-kids' => 'Family',
    ];

    public const SORTS = [
        'recommended' => 'Recommended',
        'price_low' => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'rating' => 'Top rated',
        'quickest' => 'Shortest first',
        'newest' => 'Newest',
    ];

    /**
     * Duration buckets, in minutes. Anything longer than a full day is sold as
     * a multi-day experience, which is still a single ticket.
     */
    public const DURATIONS = [
        'quick' => [0, 120],
        'half-day' => [121, 300],
        'full-day' => [301, 540],
        'multi-day' => [541, 100000],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatingMaxPrice(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->duration = 'all';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->destination = '';
        $this->sort = 'recommended';

        $this->resetPage();
    }

    public function setType(string $type): void
    {
        if (! array_key_exists($type, self::TYPES)) {
            return;
        }

        $this->type = $type;
        $this->resetPage();
    }

    /**
     * Flip between the browse grid and the full-screen reel feed.
     */
    public function setView(string $view): void
    {
        if (! in_array($view, ['grid', 'reels'], true)) {
            return;
        }

        $this->view = $view;
        $this->resetPage();
    }

    public function sortBy(string $sort): void
    {
        if (! array_key_exists($sort, self::SORTS)) {
            return;
        }

        $this->sort = $sort;
        $this->resetPage();
    }

    /**
     * Experience duration is the first filter a guest thinks in, so it gets a
     * bucket of its own rather than hiding in the sort menu.
     */
    public function setDuration(string $duration): void
    {
        if ($duration !== 'all' && ! array_key_exists($duration, self::DURATIONS)) {
            return;
        }

        $this->duration = $duration;
        $this->resetPage();
    }

    public function render(ActivitySearchQuery $searchQuery)
    {
        $perPage = $this->view === 'reels' ? 8 : $this->perPage;

        $cacheKey = sprintf(
            'experiences.index.%s.%s.%s.%s.%s.%s.%s.%s.%d',
            $this->search,
            $this->type,
            $this->duration,
            $this->destination,
            $this->sort,
            $this->view,
            $this->minPrice,
            $this->maxPrice,
            $perPage,
            $this->getPage()
        );

        $paginator = Cache::remember($cacheKey, 60, function () use ($searchQuery, $perPage) {
            return $this->queryExperiences($searchQuery)->paginate($perPage);
        });

        return view('livewire.experiences-index', [
            'cards' => $paginator->getCollection()
                ->map(fn (Activity $activity) => $this->mapExperience($activity))
                ->all(),
            'paginator' => $paginator,
            'types' => self::TYPES,
            'sorts' => self::SORTS,
            'durations' => (array) config('experiences.durations', []),
            'resultCount' => $paginator->total(),
            'isReels' => $this->view === 'reels',
            'hero' => (array) config('experiences.clips.'.config('experiences.hero'), []),
            'feedTabs' => $this->feedTabs(),
            'places' => $this->places(),
        ])->layoutData(['reels' => $this->view === 'reels']);
    }

    /**
     * Build the query from the filters this component owns.
     *
     * Category is applied here rather than through the shared search service
     * because the type pills are keyed on the category slug, which is also what
     * picks the reel footage.
     */
    private function queryExperiences(ActivitySearchQuery $searchQuery)
    {
        $query = $searchQuery->build([
            'search' => $this->search,
            'destination_id' => null,
            'category' => $this->type !== 'all' ? $this->typeLabelFor($this->type) : null,
            'min_price' => $this->minPrice === '' ? null : (float) $this->minPrice,
            'max_price' => $this->maxPrice === '' ? null : (float) $this->maxPrice,
            'sort' => null,
        ])->with('locations');

        if ($this->destination !== '') {
            $query->whereHas('locations', fn ($locations) => $locations
                ->where('type', 'meeting_point')
                ->where('name', $this->destination));
        }

        if ($this->duration !== 'all') {
            [$from, $to] = self::DURATIONS[$this->duration];
            $query->whereBetween('duration_minutes', [$from, $to]);
        }

        return $this->applySort($query);
    }

    /**
     * Sort with NULLs pushed to the back.
     *
     * Postgres sorts NULLs last on ASC but first on DESC, which would float
     * unrated and unpriced experiences to the top of the popular orderings.
     */
    private function applySort($query)
    {
        $nullsLast = 'rating IS NULL';

        match ($this->sort) {
            'price_low' => $query
                ->orderByRaw('base_price IS NULL')
                ->orderBy('base_price'),
            'price_high' => $query
                ->orderByRaw('base_price IS NULL')
                ->orderByDesc('base_price'),
            'rating' => $query
                ->orderByRaw($nullsLast)
                ->orderByDesc('rating')
                ->orderByDesc('reviews_count'),
            'quickest' => $query
                ->orderByRaw('duration_minutes IS NULL')
                ->orderBy('duration_minutes'),
            'newest' => $query->latest('created_at'),
            default => $query
                ->orderByDesc('is_featured')
                ->orderByRaw($nullsLast)
                ->orderByDesc('rating')
                ->orderByDesc('reviews_count'),
        };

        return $query;
    }

    /**
     * The feed's own tab row, mapped onto experience categories.
     */
    private function feedTabs(): array
    {
        return collect((array) config('experiences.feed_tabs', []))
            ->map(fn ($label, $key) => [
                'label' => $label,
                'type' => $key === 'for-you' ? 'all' : $key,
            ])
            ->values()
            ->all();
    }

    /**
     * Only the places that actually have experiences on sale, so the place
     * filter never offers a dead end.
     *
     * An experience is filtered by where it happens — the reef, the trailhead,
     * the market — not by the town the listing happens to be filed under.
     */
    private function places(): array
    {
        return ActivityLocation::query()
            ->where('type', 'meeting_point')
            ->whereHas('activity', fn ($query) => $query->published())
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * The stored category name behind a type pill.
     */
    private function typeLabelFor(string $type): string
    {
        $categories = ActivityCategory::query()
            ->pluck('name', 'slug')
            ->all();

        return $categories[$type] ?? self::TYPES[$type] ?? $type;
    }

    /**
     * Map an activity onto a card.
     */
    private function mapExperience(Activity $activity): array
    {
        $categorySlug = (string) ($activity->category?->slug ?? 'general');
        $clip = $this->clipFor($categorySlug, $activity->id);
        $place = $this->placeFor($activity);

        return [
            'id' => $activity->id,
            'name' => $activity->name,
            'slug' => $activity->slug,
            'category' => $categorySlug,
            'category_label' => (string) (config('experiences.type_labels.'.$categorySlug) ?: $activity->category?->name ?: 'Experience'),
            'place' => $place,
            'destination' => $activity->destination?->name,
            'caption' => Str::limit((string) ($activity->short_description ?: $activity->description), 130),
            'rating' => (float) ($activity->rating ?? 0),
            'reviews' => (int) ($activity->reviews_count ?? 0),
            'price' => (float) ($activity->base_price ?? 0),
            'currency' => $activity->currency ?: 'KSh',
            'duration' => (int) ($activity->duration_minutes ?? 0),
            'group' => (int) ($activity->capacity ?? 0),
            'min_age' => (int) ($activity->min_age ?? 0),
            'highlights' => array_slice((array) ($activity->highlights ?? []), 0, 3),
            'image' => $this->resolveImage($activity, $clip),
            'alt' => $activity->name,
            'video' => $clip['video'] ?? null,
            'poster' => $clip['poster'] ?? null,
            'clip_title' => $clip['title'] ?? null,
            'featured' => (bool) $activity->is_featured,
            'url' => route('activity.show', $activity->slug),
        ];
    }

    /**
     * Pick the reel footage for an experience.
     *
     * The clip is keyed off the experience id so the footage never reshuffles
     * between page loads, which would make the grid feel broken while scrolling.
     */
    private function clipFor(string $categorySlug, int $id): ?array
    {
        $clips = (array) config('experiences.reels.'.$categorySlug)
            ?: (array) config('experiences.reels.general', []);

        if ($clips === []) {
            return null;
        }

        return config('experiences.clips.'.$clips[crc32((string) $id) % count($clips)]);
    }

    /**
     * Where the experience actually happens.
     *
     * The meeting point is the honest answer for an experience — the lodge or
     * the trailhead, not the town the property happens to sit in.
     */
    private function placeFor(Activity $activity): string
    {
        $meetingPoint = $activity->locations?->firstWhere('type', 'meeting_point')
            ?? $activity->locations?->first();

        return (string) ($meetingPoint?->name
            ?: $activity->destination?->name
            ?: 'Kenya');
    }

    private function resolveImage(Activity $activity, ?array $clip): string
    {
        $candidates = array_filter([
            $activity->thumbnail,
            $activity->images->first()?->path ?? $activity->images->first()?->url ?? null,
            $activity->gallery[0] ?? null,
        ]);

        foreach ($candidates as $image) {
            if (filled($image)) {
                return Str::startsWith($image, ['http://', 'https://', '/'])
                    ? $image
                    : asset('storage/'.ltrim($image, '/'));
            }
        }

        return $clip['poster'] ?? asset('images/welcome-screen.jpg');
    }
}