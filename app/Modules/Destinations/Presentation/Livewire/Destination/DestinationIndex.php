<?php

namespace App\Modules\Destinations\Presentation\Livewire\Destination;

use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Visual Discovery destination index.
 *
 * Presents the catalogue in the discovery visual language: full-bleed hero search,
 * category pills, editorial tabs and an asymmetric card grid. When the catalogue is
 * still empty the curated set below is rendered instead, so the page never reads
 * as broken while the team is seeding content.
 */
#[Layout('layouts.discovery')]
class DestinationIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Which editorial collection is being browsed: all, featured or top-rated.
     */
    #[Url(as: 'view', except: 'all')]
    public string $tab = 'all';

    /**
     * Optional highlight filter, driven by the category pills.
     */
    #[Url(as: 'mood', except: 'all')]
    public string $category = 'all';

    #[Url(except: 'name')]
    public string $sortField = 'name';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    public int $perPage = 12;

    /**
     * Editorial collections shown as tabs.
     */
    public const TABS = [
        'all' => 'All destinations',
        'featured' => 'Featured',
        'top-rated' => 'Top rated',
    ];

    /**
     * Highlight pills. The value is matched against the highlights/tags arrays.
     */
    public const MOODS = [
        'all' => 'All moods',
        'beaches' => 'Beaches',
        'wild' => 'Safari',
        'mountains' => 'Mountains',
        'cities' => 'Cities',
        'islands' => 'Islands',
    ];

    /**
     * Curated destinations, used until the catalogue has records.
     */
    private const SHOWCASE = [
        [
            'name' => 'Diani Beach',
            'country' => 'Kenya',
            'city' => 'Kwale',
            'mood' => 'Beaches',
            'meta' => 'Turquoise water, white sands and barefoot afternoons',
            'rating' => '4.9',
            'places' => 46,
            'image' => 'https://images.unsplash.com/photo-1667935837291-1dc178866251?auto=format&fit=crop&w=1400&q=84',
        ],
        [
            'name' => 'Amboseli',
            'country' => 'Kenya',
            'city' => 'Kajiado',
            'mood' => 'Safari',
            'meta' => 'Elephant herds under Kilimanjaro',
            'rating' => '4.8',
            'places' => 31,
            'image' => 'https://images.unsplash.com/photo-1557178985-891ca9b9b01c?auto=format&fit=crop&w=1400&q=84',
        ],
        [
            'name' => 'Watamu',
            'country' => 'Kenya',
            'city' => 'Kilifi',
            'mood' => 'Islands',
            'meta' => 'Coral gardens and dhow sails at sunset',
            'rating' => '4.7',
            'places' => 24,
            'image' => 'https://images.unsplash.com/photo-1651860282131-e3257674ccd1?auto=format&fit=crop&w=1400&q=84',
        ],
        [
            'name' => 'Mount Kenya',
            'country' => 'Kenya',
            'city' => 'Nanyuki',
            'mood' => 'Mountains',
            'meta' => 'Glacial trails, tarns and thin bright air',
            'rating' => '4.9',
            'places' => 18,
            'image' => 'https://images.unsplash.com/photo-1613061445510-e296bfedb73e?auto=format&fit=crop&w=1400&q=84',
        ],
        [
            'name' => 'Lamu Island',
            'country' => 'Kenya',
            'city' => 'Lamu',
            'mood' => 'Islands',
            'meta' => 'Swahili lanes, dhow harbours, no cars',
            'rating' => '4.6',
            'places' => 15,
            'image' => 'https://images.unsplash.com/photo-1708119063168-4785d1359824?auto=format&fit=crop&w=1400&q=84',
        ],
        [
            'name' => 'Tsavo',
            'country' => 'Kenya',
            'city' => 'Makueni',
            'mood' => 'Wild',
            'meta' => 'Red earth, big skies and elephant highways',
            'rating' => '4.8',
            'places' => 27,
            'image' => 'https://images.unsplash.com/photo-1585402969048-86f38f8bfc2e?auto=format&fit=crop&w=1400&q=84',
        ],
    ];

    /**
     * Hero artwork and the caption pinned to its lower right corner.
     */
    private const HERO = [
        'image' => 'https://images.unsplash.com/photo-1664093671658-a2aac3a344a9?auto=format&fit=crop&w=1600&q=86',
        'alt' => 'A wooden boat floating on the turquoise water of the Kenyan coast',
        'place' => 'Lamu Channel',
        'region' => 'Indian Ocean, Kenya',
    ];

    #[On('refreshDestinations')]
    public function refreshDestinations(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    /**
     * Switch editorial collection, applying that collection's natural order.
     */
    public function setTab(string $tab): void
    {
        if (! array_key_exists($tab, self::TABS)) {
            return;
        }

        $this->tab = $tab;
        $this->sortField = $tab === 'top-rated' ? 'popularity_score' : 'name';
        $this->sortDirection = $tab === 'top-rated' ? 'desc' : 'asc';

        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function queryDestinations()
    {
        return Destination::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('country', 'like', "%{$this->search}%")
                        ->orWhere('city', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category !== 'all', function ($query) {
                $query->where(function ($q) {
                    foreach ($this->categoryValues() as $value) {
                        $q->orWhereJsonContains('highlights', $value)
                            ->orWhereJsonContains('tags', $value);
                    }
                });
            })
            ->when($this->tab === 'featured', fn ($query) => $query->where('is_featured', true))
            ->withAvg('reviews', 'rating')
            ->withCount('bookings')
            ->when($this->sortField === 'trending', function ($query) {
                $query->orderByRaw('(bookings_count * 2 + COALESCE(reviews_avg_rating, 0)) desc');
            }, function ($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            });
    }

    public function render()
    {
        $cacheKey = sprintf(
            'destinations.index.%s.%s.%s.%s.%s.%s',
            $this->search,
            $this->category,
            $this->tab,
            $this->sortField,
            $this->sortDirection,
            $this->getPage()
        );

        $paginator = Cache::remember($cacheKey, 60, function () {
            return $this->queryDestinations()->paginate($this->perPage);
        });

        return view('livewire.destination.destination-index', [
            'cards' => $this->cards($paginator),
            'paginator' => $paginator,
            'hero' => self::HERO,
            'moods' => self::MOODS,
            'tabs' => self::TABS,
            'resultCount' => $paginator->total(),
            'isFallback' => $paginator->total() === 0,
        ]);
    }

    /**
     * Normalise the highlight pill into the values a record may store.
     */
    private function categoryValues(): array
    {
        return array_values(array_unique(array_filter([
            $this->category,
            Str::headline($this->category),
            Str::singular($this->category),
        ])));
    }

    /**
     * Map paginator records (or the curated set) onto card shaped arrays.
     */
    private function cards($paginator): array
    {
        if ($paginator->total() > 0) {
            return $paginator->getCollection()
                ->map(fn (Destination $destination) => $this->mapDestination($destination))
                ->all();
        }

        if (filled($this->search) || $this->category !== 'all' || $this->tab !== 'all') {
            return [];
        }

        return array_map(fn (array $item) => [
            'name' => $item['name'],
            'kicker' => $item['mood'],
            'meta' => $item['meta'],
            'sub' => $item['rating'].' · '.$item['places'].' places to explore',
            'image' => $item['image'],
            'alt' => $item['name'].', '.$item['country'],
            'url' => route('destination.index').'?q='.urlencode($item['name']),
            'is_record' => false,
        ], self::SHOWCASE);
    }

    private function mapDestination(Destination $destination): array
    {
        $rating = (float) ($destination->reviews_avg_rating ?? 0);

        return [
            'name' => $destination->name,
            'kicker' => collect([$destination->city, $destination->country])->filter()->implode(' · '),
            'meta' => Str::limit((string) ($destination->description ?? ''), 90),
            'sub' => $rating > 0
                ? number_format($rating, 1).' · '.$destination->bookings_count.' stays booked'
                : $destination->bookings_count.' stays booked',
            'image' => $this->resolveImage($destination),
            'alt' => $destination->name,
            'url' => route('destination.show', $destination),
            'is_record' => true,
        ];
    }

    private function resolveImage(Destination $destination): string
    {
        $image = $destination->image_url
            ?? $destination->thumbnail
            ?? $destination->cover_image
            ?? null;

        if (filled($image)) {
            return Str::startsWith($image, ['http://', 'https://', '/'])
                ? $image
                : asset('storage/'.ltrim($image, '/'));
        }

        $gallery = array_values(array_filter((array) ($destination->gallery ?? [])));

        if ($gallery !== []) {
            return Str::startsWith($gallery[0], ['http://', 'https://', '/'])
                ? $gallery[0]
                : asset('storage/'.ltrim($gallery[0], '/'));
        }

        return asset('images/welcome-screen.jpg');
    }
}
