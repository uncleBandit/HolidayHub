<?php

namespace App\Modules\Destinations\Application\Services;

use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds destinations for hosts, and creates one only when it truly is new.
 *
 * Hosts add their own destinations while listing a property, so the same
 * place must not end up in the catalogue twice as "Diani", "Diani Beach" and
 * "diani beach ". Matching is always within a country and treats names as
 * equal after normalising case, accents, punctuation and filler words, then
 * falls back to near-identical spellings and to places within a few
 * kilometres with a similar name.
 */
class DestinationResolver
{
    /** Two places this close with similar names are the same place. */
    private const NEARBY_KM = 15;

    private const FILLER = ['the', 'beach', 'island', 'town', 'city', 'national', 'park', 'reserve', 'county', 'district', 'region', 'old'];

    /**
     * Destinations matching what a host typed, best first, for the picker.
     */
    public function search(string $query, ?string $country = null, int $limit = 8): Collection
    {
        $query = trim($query);

        return Destination::query()
            ->when($country, fn ($q) => $q->whereRaw('LOWER(country) = ?', [Str::lower($country)]))
            ->when($query !== '', function ($q) use ($query) {
                $like = '%'.Str::lower($query).'%';
                $q->where(fn ($w) => $w->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(city) LIKE ?', [$like]));
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('popularity_score')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'city', 'country', 'latitude', 'longitude'])
            ->merge($query === '' ? collect() : $this->similar($query, $country))
            ->unique('id')
            ->take($limit)
            ->values();
    }

    /**
     * The existing destination that is the same place, if any.
     */
    public function match(string $name, string $country, ?float $lat = null, ?float $lng = null): ?Destination
    {
        return $this->similar($name, $country, $lat, $lng)->first();
    }

    /**
     * Use the existing destination when there is one; otherwise create it.
     *
     * @return array{0: Destination, 1: bool} the destination, and whether it already existed
     */
    public function findOrCreate(string $name, string $country, ?string $city = null, ?float $lat = null, ?float $lng = null, ?int $providerId = null): array
    {
        $name = Str::of($name)->squish()->title()->toString();

        if ($existing = $this->match($name, $country, $lat, $lng)) {
            return [$existing, true];
        }

        $base = Str::slug($name.' '.$country) ?: 'destination';
        $slug = $base;
        $n = 2;
        while (Destination::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        $destination = Destination::create([
            'name' => $name,
            'slug' => $slug,
            'country' => $country,
            'city' => filled($city) ? Str::squish($city) : $name,
            'latitude' => $lat,
            'longitude' => $lng,
            'meta_data' => ['source' => 'provider', 'created_by_provider_id' => $providerId],
        ]);

        return [$destination, false];
    }

    /**
     * Other destinations the matcher would treat as the same place as this one.
     *
     * Runs the exact rules findOrCreate() uses, so an admin reviewing
     * duplicates sees the pairs a host would have been steered into.
     */
    public function duplicatesOf(Destination $destination): Collection
    {
        return $this->similar(
            $destination->name,
            $destination->country,
            $destination->latitude !== null ? (float) $destination->latitude : null,
            $destination->longitude !== null ? (float) $destination->longitude : null,
        )->reject(fn (Destination $candidate) => $candidate->is($destination))->values();
    }

    /**
     * Every destination that has at least one likely duplicate, keyed by id,
     * with the ids it duplicates.
     *
     * @return array<int, list<int>>
     */
    public function duplicateMap(): array
    {
        $map = [];

        foreach (Destination::query()->get(['id', 'name', 'city', 'country', 'latitude', 'longitude']) as $destination) {
            $ids = $this->duplicatesOf($destination)->pluck('id')->all();

            if ($ids !== []) {
                $map[$destination->id] = $ids;
            }
        }

        return $map;
    }

    /**
     * Same-country destinations that look like the same place, closest first.
     */
    private function similar(string $name, ?string $country, ?float $lat = null, ?float $lng = null): Collection
    {
        $key = $this->normalise($name);

        if ($key === '') {
            return collect();
        }

        return Destination::query()
            ->when($country, fn ($q) => $q->whereRaw('LOWER(country) = ?', [Str::lower($country)]))
            ->get(['id', 'name', 'city', 'country', 'latitude', 'longitude'])
            ->map(function (Destination $destination) use ($key, $lat, $lng) {
                $candidate = $this->normalise($destination->name);
                $city = $this->normalise((string) $destination->city);
                $distance = ($lat !== null && $lng !== null && $destination->latitude !== null && $destination->longitude !== null)
                    ? $this->km($lat, $lng, (float) $destination->latitude, (float) $destination->longitude)
                    : null;

                similar_text($key, $candidate, $percent);

                $score = match (true) {
                    $candidate === $key => 100,
                    // Containment only for real words, so "ma" never swallows "Maasai Mara".
                    min(strlen($key), strlen($candidate)) >= 4 && (Str::contains($candidate, $key) || Str::contains($key, $candidate)) => 90,
                    strlen($key) >= 5 && levenshtein($key, $candidate) <= 2 => 85,
                    $city !== '' && $city === $key => 80,
                    $distance !== null && $distance <= self::NEARBY_KM && $percent >= 60 => 75,
                    default => 0,
                };

                return $score > 0 ? ['destination' => $destination, 'score' => $score, 'distance' => $distance ?? INF] : null;
            })
            ->filter()
            ->sortBy([['score', 'desc'], ['distance', 'asc']])
            ->pluck('destination')
            ->values();
    }

    /**
     * "The Diani Beach!" and "diani" both become "diani".
     */
    private function normalise(string $name): string
    {
        $words = Str::of(Str::ascii($name))->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->explode(' ');
        $kept = $words->reject(fn ($word) => in_array($word, self::FILLER, true));

        return ($kept->isEmpty() ? $words : $kept)->implode(' ');
    }

    private function km(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
