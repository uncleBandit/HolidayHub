<?php

namespace App\Modules\Destinations\Presentation\Livewire\Home;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Editorial landing page for the public site.
 *
 * Renders the magazine-style discovery home: hero destination mosaic, a floating
 * trip search bar, a filterable collection of stays and the field-journal strip.
 * Everything degrades gracefully when the catalogue is still empty, in which case
 * the curated showcase content below is used instead of database records.
 */
class HomeLanding extends Component
{
    /**
     * The landing page renders inside the site's shared layout.
     */
    #[Layout('layouts.app')]

    /**
     * Filter chips for the stays grid.
     */
    #[Url(as: 'collection', except: 'for-you')]
    public string $category = 'for-you';

    /**
     * Comma separated list of saved stay names, kept in the URL so a visitor can
     * share or reload without losing their picks.
     */
    public string $saved = '';

    public string $tripWhere = '';

    public string $tripWhen = '';

    public string $tripGuests = '';

    /**
     * Categories exposed as pills. The key is the Livewire state, the label is
     * what the visitor sees.
     */
    public const CATEGORIES = [
        'for-you' => 'For you',
        'hotels' => 'Stays',
        'villas' => 'Villas',
        'breakfasts' => 'B&Bs',
        'beaches' => 'Beaches',
        'wild' => 'In the wild',
    ];

    /**
     * Curated hero destinations, used until destinations exist in the catalogue.
     * The images are the ones from the Figma design.
     */
    private const SHOWCASE_DESTINATIONS = [
        [
            'name' => 'Kyoto',
            'country' => 'Japan',
            'tag' => 'Culture',
            'meta' => 'Sacred paths & quiet rituals',
            'image' => 'https://images.unsplash.com/photo-1545569341-9eb8b30979d9?auto=format&fit=crop&w=1200&q=85',
        ],
        [
            'name' => 'Sahara',
            'country' => 'Morocco',
            'tag' => 'Adventure',
            'meta' => 'Desert camps under the stars',
            'image' => 'https://images.unsplash.com/photo-1559586616-361e18714958?auto=format&fit=crop&w=1200&q=85',
        ],
        [
            'name' => 'Vernazza',
            'country' => 'Italy',
            'tag' => 'Coast',
            'meta' => 'Cliffside villages & sea',
            'image' => 'https://images.unsplash.com/photo-1534445867742-43195f401b6c?auto=format&fit=crop&w=1200&q=85',
        ],
    ];

    /**
     * Curated stays, used until hotels, villas or B&Bs exist in the catalogue.
     */
    private const SHOWCASE_STAYS = [
        [
            'name' => 'Casa Bellavista',
            'location' => 'Amalfi Coast, Italy',
            'badge' => 'Guest favorite',
            'rating' => '4.96',
            'reviews' => '184 reviews',
            'price' => '$420',
            'image' => 'https://images.unsplash.com/photo-1561501900-3701fa6a0864?auto=format&fit=crop&w=1000&q=85',
        ],
        [
            'name' => 'Aman Kyoto',
            'location' => 'Kyoto, Japan',
            'badge' => 'Exceptional stay',
            'rating' => '4.91',
            'reviews' => '127 reviews',
            'price' => '$680',
            'image' => 'https://images.unsplash.com/photo-1574236170880-fbbca132d83d?auto=format&fit=crop&w=1000&q=85',
        ],
        [
            'name' => 'Azalai Desert Camp',
            'location' => 'Merzouga, Morocco',
            'badge' => 'Rare find',
            'rating' => '4.89',
            'reviews' => '216 reviews',
            'price' => '$295',
            'image' => 'https://images.unsplash.com/photo-1613169620329-6785c004d900?auto=format&fit=crop&w=1000&q=85',
        ],
    ];

    private const FALLBACK_STAYS = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1000&q=85',
        'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1000&q=85',
        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1000&q=85',
    ];

    private const JOURNAL_IMAGE = 'https://images.unsplash.com/photo-1605523746900-8aa30db1befd?auto=format&fit=crop&w=1400&q=85';

    /**
     * Hand the trip search bar over to the destinations index.
     */
    public function runTripSearch(): void
    {
        $this->redirectRoute('destination.index', array_filter([
            'q' => trim($this->tripWhere),
            'check_in' => $this->tripWhen,
            'guests' => $this->tripGuests,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function selectCategory(string $category): void
    {
        $this->category = array_key_exists($category, self::CATEGORIES) ? $category : 'for-you';
    }

    /**
     * Toggle the heart on a stay card. The state lives in the URL so a visitor
     * can share / reload the page without losing their picks.
     */
    public function toggleSave(string $name): void
    {
        $saved = array_filter(explode('||', $this->saved));

        $key = trim($name);

        if (in_array($key, $saved, true)) {
            $saved = array_values(array_diff($saved, [$key]));
        } else {
            $saved[] = $key;
        }

        $this->saved = implode('||', $saved);
    }

    public function isSaved(string $name): bool
    {
        return in_array(trim($name), array_filter(explode('||', $this->saved)), true);
    }

    public function render()
    {
        return view('livewire.home.home-landing', [
            'heroDestinations' => $this->heroDestinations(),
            'stays' => $this->stays(),
            'categories' => self::CATEGORIES,
            'journalImage' => self::JOURNAL_IMAGE,
        ]);
    }

    /**
     * Featured destinations for the hero mosaic, padded with the curated set so
     * the mosaic always has its three panels.
     */
    private function heroDestinations(): array
    {
        $records = Cache::remember('landing.hero_destinations', now()->addMinutes(10), fn () => Destination::query()
            ->orderByDesc('is_featured')
            ->orderByDesc('popularity_score')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn (Destination $destination) => [
                'name' => $destination->name,
                'country' => $destination->country ?: ($destination->city ?? 'Somewhere wonderful'),
                'tag' => $this->firstTag($destination),
                'meta' => $destination->tagline ?: $this->truncate($destination->short_description ?: $destination->description, 42),
                'image' => $this->resolveImage($destination),
                'url' => route('destination.show', $destination),
                'is_record' => true,
            ])
            ->all());

        $cards = $records;

        foreach (self::SHOWCASE_DESTINATIONS as $fallback) {
            if (count($cards) >= 3) {
                break;
            }

            $cards[] = $fallback + [
                'url' => route('destination.index'),
                'is_record' => false,
            ];
        }

        return array_values($cards);
    }

    /**
     * Stays for the "Handpicked for you" grid: hotels, villas and B&Bs merged
     * into one editorial list, filtered by the active category pill.
     */
    private function stays(): array
    {
        $records = Cache::remember('landing.stays.'.$this->category, now()->addMinutes(10), function () {
            $sources = array_filter([
                $this->category === 'villas' ? Villa::class : null,
                $this->category === 'breakfasts' ? BedAndBreakfast::class : null,
                in_array($this->category, ['for-you', 'hotels'], true) ? Hotel::class : null,
            ]);

            $beachKeywords = ['beach', 'coast', 'island', 'seaside', 'ocean', 'lagoon', 'bay'];
            $wildKeywords = ['safari', 'mountain', 'forest', 'desert', 'camp', 'wilderness', 'reef', 'trek'];

            return collect($sources)
                ->flatMap(function (string $model) use ($beachKeywords, $wildKeywords) {
                    $items = $model::query()->published()->with('accommodation')->limit(12)->get();

                    if ($this->category === 'beaches' || $this->category === 'wild') {
                        $keywords = $this->category === 'beaches' ? $beachKeywords : $wildKeywords;

                        $items = $items->filter(fn ($item) => $this->matchesKeywords($item, $keywords));
                    }

                    return $items->map(fn ($item) => $this->mapStay($item));
                })
                ->sortByDesc(fn (array $stay) => (float) $stay['rating'])
                ->take(3)
                ->values()
                ->all();
        });

        $cards = $records;

        // Top the grid up with the curated stays so a category never renders empty.
        foreach (self::SHOWCASE_STAYS as $fallback) {
            if (count($cards) >= 3) {
                break;
            }

            $cards[] = $fallback + [
                'url' => route('stays.index'),
                'slug' => $fallback['name'],
                'is_record' => false,
            ];
        }

        foreach ($cards as $index => $card) {
            $cards[$index]['media_count'] = max(1, (int) ($card['media_count'] ?? 1));
        }

        return array_values($cards);
    }

    /**
     * Normalise a bookable model (hotel, villa, B&B) into the card shape used by
     * the view.
     */
    private function mapStay($item): array
    {
        $accommodation = $item->accommodation;
        $images = method_exists($item, 'getAllImagesAttribute')
            ? array_values(array_filter($item->all_images))
            : [];

        $gallery = array_values(array_filter((array) ($item->gallery ?? [])));

        $image = $images[0]
            ?? $this->storageUrl($item->cover_image ?? null)
            ?? ($gallery[0] ? $this->storageUrl($gallery[0]) : null);

        $rating = (float) ($accommodation?->avg_rating ?? 0);
        $reviews = (int) ($accommodation?->reviews_count ?? 0);

        return [
            'name' => $accommodation?->name,
            'location' => collect([$accommodation?->city, $accommodation?->country])->filter()->implode(', '),
            'badge' => ($accommodation?->is_featured ?? false) ? 'Guest favorite' : ($rating >= 4.8 ? 'Exceptional stay' : 'Rare find'),
            'rating' => $rating > 0 ? number_format($rating, 2) : null,
            'reviews' => $reviews > 0 ? $reviews.' reviews' : 'New listing',
            'price' => $accommodation?->avg_price_per_night ? '$'.number_format((float) $accommodation->avg_price_per_night, 0) : null,
            'price_suffix' => $accommodation?->avg_price_per_night ? '/ night' : '',
            'image' => $image ?? self::FALLBACK_STAYS[abs($item->id ?? 0) % count(self::FALLBACK_STAYS)],
            'media_count' => count($images) ?: (count($gallery) ?: 1),
            'url' => $this->stayUrl($item),
            'slug' => $item->name,
            'is_record' => true,
        ];
    }

    /**
     * URL for a stay card, based on the model type.
     */
    private function stayUrl($item): string
    {
        return match (true) {
            $item instanceof Villa => route('villa.show', $item),
            $item instanceof BedAndBreakfast => route('bedandbreakfast.show', $item),
            $item instanceof Hotel => route('hotel-show', $item),
            default => route('stays.index'),
        };
    }

    /**
     * Destination artwork, falling back to its thumbnail then gallery then a
     * bundled local image.
     */
    private function resolveImage($item): string
    {
        $image = $item->image_url
            ?? $item->thumbnail
            ?? $item->cover_image
            ?? null;

        if (filled($image)) {
            return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : $this->storageUrl($image);
        }

        $gallery = array_values(array_filter((array) ($item->gallery ?? [])));

        if ($gallery !== []) {
            return $this->storageUrl($gallery[0]);
        }

        return asset('images/welcome-screen.jpg');
    }

    private function storageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : asset('storage/'.ltrim($path, '/'));
    }

    private function firstTag(Destination $destination): string
    {
        $tags = array_values(array_filter((array) ($destination->tags ?? [])));

        if ($tags !== []) {
            return Str::headline((string) $tags[0]);
        }

        $highlights = array_values(array_filter((array) ($destination->highlights ?? [])));

        if ($highlights !== []) {
            return Str::headline((string) $highlights[0]);
        }

        return 'Escape';
    }

    private function matchesKeywords($item, array $keywords): bool
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $item->name ?? null,
            $item->city ?? null,
            $item->country ?? null,
            $item->description ?? null,
            implode(' ', (array) ($item->tags ?? [])),
        ])));

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function truncate(?string $value, int $limit): string
    {
        return Str::limit((string) $value, $limit);
    }
}
