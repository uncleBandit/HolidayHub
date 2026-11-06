<?php

namespace App\Modules\Destinations\Presentation\Livewire;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Discover: travel by feeling rather than by search.
 *
 * A cinematic hero cycles through places, the mood picker swaps an editorial
 * spread, "Surprise me" deals a random escape client-side, and the reel strip
 * plays house footage as it scrolls into view. Curated places carry the page
 * until the catalogue has destinations with real photography.
 */
#[Layout('layouts.discovery')]
class Discover extends Component
{
    private const PHOTO = 'https://images.unsplash.com/photo-%s?auto=format&fit=crop&w=%d&q=84';

    /**
     * Moods and the places that answer them. Images are from the Figma design
     * and the curated showcase sets used elsewhere on the site.
     */
    public const MOODS = [
        'golden' => [
            'label' => 'Golden hour',
            'icon' => 'waves',
            'line' => 'Warm water, long light and nowhere else to be.',
            'tint' => '#E8A35A',
            'places' => [
                ['name' => 'Diani Beach', 'country' => 'Kenya', 'line' => 'White sand that glows at five o’clock', 'photo' => '1667935837291-1dc178866251'],
                ['name' => 'Watamu', 'country' => 'Kenya', 'line' => 'Coral coves and turtle tides', 'photo' => '1651860282131-e3257674ccd1'],
                ['name' => 'The Swahili coast', 'country' => 'Kenya', 'line' => 'Sail a dhow into the sunset', 'photo' => '1664093671658-a2aac3a344a9'],
            ],
        ],
        'wild' => [
            'label' => 'Into the wild',
            'icon' => 'compass',
            'line' => 'Wake to elephants, fall asleep to lions.',
            'tint' => '#C9893F',
            'places' => [
                ['name' => 'Amboseli', 'country' => 'Kenya', 'line' => 'Giants walking beneath Kilimanjaro', 'photo' => '1557178985-891ca9b9b01c'],
                ['name' => 'Tsavo', 'country' => 'Kenya', 'line' => 'Red earth and endless horizons', 'photo' => '1585402969048-86f38f8bfc2e'],
                ['name' => 'Sahara', 'country' => 'Morocco', 'line' => 'Desert camps under a sky full of stars', 'photo' => '1559586616-361e18714958'],
            ],
        ],
        'heights' => [
            'label' => 'Up high',
            'icon' => 'mountain',
            'line' => 'Thin air, big views and well-earned silence.',
            'tint' => '#6E9A84',
            'places' => [
                ['name' => 'Mount Kenya', 'country' => 'Kenya', 'line' => 'Glaciers on the equator', 'photo' => '1613061445510-e296bfedb73e'],
                ['name' => 'The Aberdares', 'country' => 'Kenya', 'line' => 'Trails through tall grass and mist', 'photo' => '1707819487421-21228af983a9'],
                ['name' => 'Atlas foothills', 'country' => 'Morocco', 'line' => 'Camp where the desert meets the peaks', 'photo' => '1613169620329-6785c004d900'],
            ],
        ],
        'barefoot' => [
            'label' => 'Barefoot & salty',
            'icon' => 'waves',
            'line' => 'Pastel villages, pool days and sea-salt hair.',
            'tint' => '#4FA3B5',
            'places' => [
                ['name' => 'Lamu Island', 'country' => 'Kenya', 'line' => 'No cars, just donkeys and dhows', 'photo' => '1708119063168-4785d1359824'],
                ['name' => 'Vernazza', 'country' => 'Italy', 'line' => 'A harbour painted in sorbet colours', 'photo' => '1534445867742-43195f401b6c'],
                ['name' => 'Casa Bellavista', 'country' => 'Italy', 'line' => 'A terrace made for long lunches', 'photo' => '1561501900-3701fa6a0864'],
            ],
        ],
        'slow' => [
            'label' => 'Slow & quiet',
            'icon' => 'leaf',
            'line' => 'Temple gardens, tea rituals and soft mornings.',
            'tint' => '#A88BB8',
            'places' => [
                ['name' => 'Kyoto', 'country' => 'Japan', 'line' => 'Sacred paths and quiet rituals', 'photo' => '1545569341-9eb8b30979d9'],
                ['name' => 'Arashiyama', 'country' => 'Japan', 'line' => 'Rooms that open onto the forest', 'photo' => '1574236170880-fbbca132d83d'],
            ],
        ],
    ];

    /**
     * Hero slides, drawn from the moods so every slide links somewhere real.
     */
    private const HERO = [
        ['golden', 0], ['wild', 0], ['slow', 0], ['wild', 2], ['barefoot', 1],
    ];

    /**
     * Words that cycle through the hero headline.
     */
    public const WONDERS = ['golden.', 'wild.', 'quiet.', 'barefoot.', 'like home.', 'unforgettable.'];

    #[Url(as: 'mood', except: 'golden')]
    public string $mood = 'golden';

    public function setMood(string $mood): void
    {
        if (array_key_exists($mood, self::MOODS)) {
            $this->mood = $mood;
        }
    }

    public function render()
    {
        $places = $this->places();

        return view('livewire.discover', [
            'hero' => collect(self::HERO)->map(fn ($ref) => $places[$ref[0]][$ref[1]])->values(),
            'wonders' => self::WONDERS,
            'moods' => collect(self::MOODS)->map(fn ($mood) => collect($mood)->except('places')->all()),
            'current' => ['places' => $places[$this->mood]] + self::MOODS[$this->mood],
            'deck' => collect($places)->flatten(1)->values(),
            'reels' => $this->reels(),
            'destinations' => $this->destinations(),
        ])->title('Discover · HolidayHub');
    }

    /**
     * Curated places keyed by mood, with image URLs and links resolved.
     */
    private function places(): array
    {
        return collect(self::MOODS)->map(fn ($mood, $key) => collect($mood['places'])->map(fn ($place) => [
            'name' => $place['name'],
            'country' => $place['country'],
            'line' => $place['line'],
            'mood' => $mood['label'],
            'image' => sprintf(self::PHOTO, $place['photo'], 1600),
            'thumb' => sprintf(self::PHOTO, $place['photo'], 900),
            'url' => route('destination.index').'?q='.urlencode($place['name']),
        ])->all())->all();
    }

    /**
     * Moving footage: real published stays paired with house clips, or the
     * clips alone while nothing is published yet.
     */
    private function reels(): Collection
    {
        $clips = collect(config('stays.clips', []))->values();

        if ($clips->isEmpty()) {
            return collect();
        }

        $stays = Cache::remember('discover.reel_stays', 300, fn () => Accommodation::published()
            ->with(['bookable', 'destination'])
            ->orderByDesc('is_featured')
            ->latest()
            ->take(8)
            ->get());

        if ($stays->isEmpty()) {
            return $clips->take(8)->map(fn ($clip) => [
                'title' => Str::of($clip['title'])->before(' with ')->toString(),
                'place' => 'HolidayHub stays',
                'video' => $clip['video'],
                'poster' => $clip['poster'],
                'url' => route('stays.index'),
            ]);
        }

        return $stays->map(function (Accommodation $stay) use ($clips) {
            $clip = $clips[crc32((string) $stay->id) % $clips->count()];
            $bookable = $stay->bookable;

            return [
                'title' => $bookable?->name ?: $stay->name,
                'place' => $stay->destination?->name ?: ($bookable?->city ?: $stay->country),
                'video' => $clip['video'],
                'poster' => $clip['poster'],
                'url' => match ($stay->bookable_type) {
                    'hotel' => route('hotel-show', $bookable),
                    'villa' => route('villa.show', $bookable),
                    'bed_and_breakfast' => route('bedandbreakfast.show', $bookable),
                    default => route('stays.index'),
                },
            ];
        });
    }

    /**
     * Destinations from the catalogue that have real photography. Seeded
     * placeholder images would break the spell, so those are skipped.
     */
    private function destinations(): Collection
    {
        return Cache::remember('discover.destinations', 300, fn () => Destination::query()
            ->orderByDesc('is_featured')
            ->orderByDesc('popularity_score')
            ->take(24)
            ->get())
            ->map(function (Destination $destination) {
                $image = $destination->image_url ?: $destination->thumbnail;

                if (blank($image) || Str::contains($image, ['placeholder', 'placehold', 'picsum', 'lorempixel'])) {
                    return null;
                }

                return [
                    'name' => $destination->name,
                    'sub' => $destination->tagline ?: collect([$destination->city, $destination->country])->filter()->implode(' · '),
                    'image' => Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/')),
                    'url' => route('destination.show', $destination),
                ];
            })
            ->filter()
            ->take(6)
            ->values();
    }
}
