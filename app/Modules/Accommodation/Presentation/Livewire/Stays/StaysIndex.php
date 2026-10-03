<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Stays;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The unified stays section.
 *
 * Hotels, villas and B&Bs are three separate modules with three separate tables,
 * but for a visitor a stay is a stay. This component is the single front door:
 * it queries the canonical `accommodations` table (which carries the publishing
 * state), resolves each row's real content through the polymorphic bookable,
 * and presents the result in the reels-first discovery language.
 *
 * Two presentations of the same result set:
 *   grid  - a hover-to-play card grid, the browse view
 *   reels - a full-screen vertical feed, the discovery view
 */
#[Layout('layouts.discovery')]
class StaysIndex extends Component
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

    /**
     * Nightly price range, carried over from the per-type listings.
     */
    #[Url(as: 'min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max', except: '')]
    public string $maxPrice = '';

    /**
     * Stay types, mapped to the table that holds the real content.
     */
    public const TYPES = [
        'all' => 'All stays',
        'hotel' => 'Hotels',
        'villa' => 'Villas',
        'bed_and_breakfast' => 'B&Bs',
    ];

    /**
     * Bookable alias => [table, singular label, price column].
     */
    private const BOOKABLES = [
        'hotel' => ['table' => 'hotels', 'label' => 'Hotel', 'price' => 'avg_price_per_night'],
        'villa' => ['table' => 'villas', 'label' => 'Villa', 'price' => 'avg_price_per_night'],
        'bed_and_breakfast' => ['table' => 'bed_and_breakfasts', 'label' => 'B&B', 'price' => 'price_per_night'],
    ];

    public const SORTS = [
        'recommended' => 'Recommended',
        'price_low' => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'rating' => 'Top rated',
        'newest' => 'Newest',
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

    /**
     * Clear every filter except the stay type.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->minPrice = '';
        $this->maxPrice = '';
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

    protected function queryStays()
    {
        return Accommodation::query()
            ->published()
            ->whereNotNull('bookable_id')
            ->when($this->type !== 'all', fn ($query) => $query->where('bookable_type', $this->type))
            ->when($this->search, fn ($query) => $this->applySearch($query))
            ->when($this->hasPriceRange(), fn ($query) => $this->applyPriceRange($query))
            ->with(['bookable', 'destination'])
            ->tap(fn ($query) => $this->applySort($query));
    }

    private function hasPriceRange(): bool
    {
        return $this->minPrice !== '' || $this->maxPrice !== '';
    }

    /**
     * Search the canonical row plus every bookable table.
     *
     * `bookable` is a morphTo, so it cannot be filtered with whereHas(); each
     * type gets its own correlated EXISTS instead.
     */
    private function applySearch($query): void
    {
        $term = '%'.$this->search.'%';

        $query->where(function ($query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhere('city', 'like', $term)
                ->orWhere('country', 'like', $term);

            foreach (self::BOOKABLES as $alias => $bookable) {
                $query->orWhere(function ($query) use ($alias, $bookable, $term) {
                    $query->where('bookable_type', $alias)
                        ->whereExists($this->bookableExists($bookable, function ($where) use ($bookable, $term) {
                            $where->where('name', 'like', $term)
                                ->orWhere('city', 'like', $term)
                                ->orWhere('country', 'like', $term);
                        }));
                });
            }
        });
    }

    /**
     * Price range filtering.
     *
     * The canonical `avg_price_per_night` is only denormalised for part of the
     * catalogue, so the range is matched against each bookable's own price
     * column: B&Bs store `price_per_night`, hotels and villas `avg_price_per_night`.
     */
    private function applyPriceRange($query): void
    {
        $min = $this->minPrice === '' ? null : (float) $this->minPrice;
        $max = $this->maxPrice === '' ? null : (float) $this->maxPrice;

        $query->where(function ($query) use ($min, $max) {
            foreach (self::BOOKABLES as $alias => $bookable) {
                $query->orWhere(function ($query) use ($alias, $bookable, $min, $max) {
                    $query->where('bookable_type', $alias)
                        ->whereExists($this->bookableExists($bookable, function ($where) use ($bookable, $min, $max) {
                            $where->whereNotNull($bookable['price'])
                                ->when($min !== null, fn ($price) => $price->where($bookable['price'], '>=', $min))
                                ->when($max !== null, fn ($price) => $price->where($bookable['price'], '<=', $max));
                        }));
                });
            }
        });
    }

    /**
     * A correlated EXISTS against one bookable table.
     */
    private function bookableExists(array $bookable, callable $constraint)
    {
        return function ($sub) use ($bookable, $constraint) {
            $sub->selectRaw('1')
                ->from($bookable['table'])
                ->whereColumn('bookable_id', $bookable['table'].'.id')
                ->whereNull($bookable['table'].'.deleted_at')
                ->where(function ($where) use ($constraint) {
                    $constraint($where);
                });
        };
    }

    /**
     * Sort with NULLs pushed to the back.
     *
     * Postgres sorts NULLs last on ASC but first on DESC, which would float
     * unrated and unpriced stays to the top of the popular orderings.
     */
    private function applySort($query): void
    {
        $nullsLast = 'avg_rating IS NULL';

        match ($this->sort) {
            'price_low' => $query
                ->orderByRaw('avg_price_per_night IS NULL')
                ->orderBy('avg_price_per_night'),
            'price_high' => $query
                ->orderByRaw('avg_price_per_night IS NULL')
                ->orderByDesc('avg_price_per_night'),
            'rating' => $query
                ->orderByRaw($nullsLast)
                ->orderByDesc('avg_rating')
                ->orderByDesc('reviews_count'),
            'newest' => $query->latest('created_at'),
            default => $query
                ->orderByDesc('is_featured')
                ->orderByRaw($nullsLast)
                ->orderByDesc('avg_rating')
                ->orderByDesc('reviews_count'),
        };
    }

    public function render()
    {
        $perPage = $this->view === 'reels' ? 8 : $this->perPage;

        $cacheKey = sprintf(
            'stays.index.%s.%s.%s.%s.%s.%s.%s.%d',
            $this->search,
            $this->type,
            $this->sort,
            $this->view,
            $this->minPrice,
            $this->maxPrice,
            $perPage,
            $this->getPage()
        );

        $paginator = Cache::remember($cacheKey, 60, function () use ($perPage) {
            return $this->queryStays()->paginate($perPage);
        });

        return view('livewire.stays.stays-index', [
            'cards' => $paginator->getCollection()
                ->map(fn (Accommodation $stay) => $this->mapStay($stay))
                ->all(),
            'paginator' => $paginator,
            'types' => self::TYPES,
            'sorts' => self::SORTS,
            'resultCount' => $paginator->total(),
            'isReels' => $this->view === 'reels',
            'hero' => (array) config('stays.clips.'.config('stays.hero'), []),
            'feedTabs' => $this->feedTabs(),
        ])->layoutData(['reels' => $this->view === 'reels']);
    }

    /**
     * The feed's own tab row, mapped onto stay types.
     */
    private function feedTabs(): array
    {
        return collect((array) config('stays.feed_tabs', []))
            ->map(fn ($label, $key) => [
                'label' => $label,
                'type' => match ($key) {
                    'hotels' => 'hotel',
                    'villas' => 'villa',
                    'bnb' => 'bed_and_breakfast',
                    default => 'all',
                },
            ])
            ->values()
            ->all();
    }

    /**
     * Map a canonical row onto a card, resolving the real content through the
     * bookable and falling back to the denormalised canonical columns.
     */
    private function mapStay(Accommodation $stay): array
    {
        $bookable = $stay->bookable;
        $type = (string) $stay->bookable_type;
        $name = $bookable?->name ?: $stay->name;
        $clip = $this->clipFor($type, $stay->id);
        $rating = (float) ($bookable->avg_rating ?? $stay->avg_rating ?? 0);
        $reviews = (int) ($bookable->reviews_count ?? $stay->reviews_count ?? 0);

        return [
            'id' => $stay->id,
            'name' => $name ?: 'Stay in '.$this->placeFor($stay, $bookable),
            'type' => $type,
            'type_label' => self::BOOKABLES[$type]['label'] ?? 'Stay',
            'place' => $this->placeFor($stay, $bookable),
            'destination' => $stay->destination?->name,
            'caption' => Str::limit((string) ($bookable?->description ?: $stay->description), 120),
            'rating' => $rating,
            'reviews' => $reviews,
            'price' => $this->priceFor($stay, $bookable),
            'currency' => $stay->destination?->currency_symbol ?: 'KSh',
            'guests' => (int) ($bookable?->max_guests ?? 0),
            'image' => $this->resolveImage($bookable, $stay, $clip),
            'alt' => $name,
            'video' => $clip['video'] ?? null,
            'poster' => $clip['poster'] ?? null,
            'clip_title' => $clip['title'] ?? null,
            'featured' => (bool) ($bookable?->is_featured ?? $stay->is_featured),
            'url' => $this->urlFor($stay, $bookable),
        ];
    }

    /**
     * Pick the reel footage for a stay.
     *
     * The clip is keyed off the stay id so the footage never reshuffles between
     * page loads, which would make the grid feel broken while scrolling.
     */
    private function clipFor(string $type, int $id): ?array
    {
        $clips = (array) config('stays.reels.'.$type, []);

        if ($clips === []) {
            return null;
        }

        return config('stays.clips.'.$clips[crc32((string) $id) % count($clips)]);
    }

    private function placeFor(Accommodation $stay, $bookable): string
    {
        $place = collect([$bookable?->city, $bookable?->country ?: $stay->country])
            ->filter()
            ->implode(', ');

        return $place ?: ($stay->destination?->name ?: 'Kenya');
    }

    private function priceFor(Accommodation $stay, $bookable): float
    {
        return (float) ($bookable?->price_per_night
            ?? $bookable?->avg_price_per_night
            ?? $stay->avg_price_per_night
            ?? 0);
    }

    private function resolveImage($bookable, Accommodation $stay, ?array $clip): string
    {
        $candidates = array_filter([
            $bookable?->cover_image,
            $bookable?->gallery[0] ?? null,
            $stay->cover_image,
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

    private function urlFor(Accommodation $stay, $bookable): string
    {
        if ($bookable === null) {
            return route('stays.index');
        }

        return match ($stay->bookable_type) {
            'hotel' => route('hotel-show', $bookable),
            'villa' => route('villa.show', $bookable),
            'bed_and_breakfast' => route('bedandbreakfast.show', $bookable),
            default => route('stays.index'),
        };
    }
}