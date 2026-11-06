<?php

namespace App\Modules\Packages\Application\Services;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Presentation\Support\TripPresenter;
use App\Shared\Support\Currencies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds the published stays and experiences an agent can build a package
 * from, as small cards: name, place, a thumbnail, the live "from" price in
 * the listing's own currency, and what can be chosen inside (a hotel's room
 * types, an experience's options).
 */
class PackageListingSearch
{
    public const TYPES = [
        'all' => 'Everything',
        'hotel' => 'Hotels',
        'villa' => 'Villas',
        'bed_and_breakfast' => 'B&Bs',
        'activity' => 'Experiences',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $term = '', string $type = 'all', ?int $destinationId = null, int $limit = 12): Collection
    {
        $term = trim($term);
        $results = collect();

        if (in_array($type, ['all', 'hotel'], true)) {
            $results = $results->concat($this->stays(Hotel::query()->with(['roomTypes', 'accommodation.destination']), $term, $destinationId, $limit)
                ->map(fn (Hotel $hotel) => $this->card('hotel', $hotel, [
                    'options' => $hotel->roomTypes->sortBy('price_per_night')->map(fn ($room) => [
                        'id' => $room->id,
                        'type' => 'room_type',
                        'label' => $room->name,
                        'sub' => 'Sleeps '.(int) ($room->capacity ?: 2),
                        'price' => (float) $room->price_per_night,
                        'currency' => $room->getCurrency(),
                    ])->values()->all(),
                    'price' => (float) ($hotel->roomTypes->min('price_per_night') ?: $hotel->avg_price_per_night),
                    'currency' => $hotel->getCurrency(),
                    'unit' => 'night',
                ])));
        }

        if (in_array($type, ['all', 'villa'], true)) {
            $results = $results->concat($this->stays(Villa::query()->with('accommodation.destination'), $term, $destinationId, $limit)
                ->map(fn (Villa $villa) => $this->card('villa', $villa, [
                    'price' => (float) $villa->avg_price_per_night,
                    'currency' => $villa->getCurrency(),
                    'unit' => 'night',
                    'sub' => 'Sleeps '.(int) $villa->max_guests,
                ])));
        }

        if (in_array($type, ['all', 'bed_and_breakfast'], true)) {
            $results = $results->concat($this->stays(BedAndBreakfast::query()->with('accommodation.destination'), $term, $destinationId, $limit)
                ->map(fn (BedAndBreakfast $bnb) => $this->card('bed_and_breakfast', $bnb, [
                    'price' => (float) $bnb->price_per_night,
                    'currency' => $bnb->getCurrency(),
                    'unit' => 'night',
                    'sub' => 'Sleeps '.(int) $bnb->max_guests,
                ])));
        }

        if (in_array($type, ['all', 'activity'], true)) {
            $activities = Activity::published()
                ->with(['destination', 'options' => fn ($q) => $q->where('is_active', true)])
                ->when($destinationId, fn ($q) => $q->where('destination_id', $destinationId))
                ->when($term !== '', fn ($q) => $this->matching($q, $term, ['name', 'short_description'], fn ($d) => $d->whereHas('destination', fn ($dest) => $this->matching($dest, $term, ['name', 'country']))))
                ->orderByDesc('rating')
                ->limit($limit)
                ->get();

            $results = $results->concat($activities->map(fn (Activity $activity) => $this->card('activity', $activity, [
                'place' => $activity->destination?->name,
                'price' => (float) $activity->base_price,
                'currency' => $activity->getCurrency(),
                'unit' => 'person',
                'sub' => $activity->duration_minutes ? $this->duration((int) $activity->duration_minutes) : null,
                'options' => $activity->options->map(fn ($option) => [
                    'id' => $option->id,
                    'type' => 'activity',
                    'label' => $option->name,
                    'sub' => $option->duration_minutes ? $this->duration((int) $option->duration_minutes) : null,
                    'price' => (float) $option->base_price,
                    'currency' => $option->getCurrency(),
                ])->values()->all(),
            ])));
        }

        return $results->take($type === 'all' ? $limit * 2 : $limit)->values();
    }

    private function stays(Builder $query, string $term, ?int $destinationId, int $limit): Collection
    {
        return $query->published()
            ->when($destinationId, fn ($q) => $q->whereHas('accommodation', fn ($a) => $a->where('destination_id', $destinationId)))
            ->when($term !== '', fn ($q) => $this->matching($q, $term, ['name', 'city', 'country'], fn ($or) => $or->whereHas('accommodation.destination', fn ($dest) => $this->matching($dest, $term, ['name']))))
            ->orderByDesc('avg_rating')
            ->limit($limit)
            ->get();
    }

    /** Case-insensitive match on any of the columns (or the extra clause). */
    private function matching(Builder $query, string $term, array $columns, ?callable $orWhere = null): Builder
    {
        $like = '%'.Str::lower(addcslashes($term, '%_\\')).'%';

        return $query->where(function ($q) use ($columns, $like, $orWhere) {
            foreach ($columns as $column) {
                $q->orWhereRaw('LOWER('.$column.') LIKE ?', [$like]);
            }
            if ($orWhere) {
                $q->orWhere(fn ($inner) => $orWhere($inner));
            }
        });
    }

    private function card(string $type, $listing, array $extra): array
    {
        $destination = $type === 'activity' ? $listing->destination : $listing->accommodation?->destination;

        return $extra + [
            'key' => $type.':'.$listing->id,
            'type' => $type,
            'id' => $listing->id,
            'name' => (string) $listing->name,
            'kind' => match ($type) {
                'hotel' => 'Hotel',
                'villa' => 'Villa',
                'bed_and_breakfast' => 'B&B',
                default => 'Experience',
            },
            'place' => $extra['place'] ?? ($destination?->name ?? collect([$listing->city ?? null, $listing->country ?? null])->filter()->implode(', ')),
            'destination_id' => $destination?->id,
            'image' => TripPresenter::image($listing, $type, 400),
            'options' => $extra['options'] ?? [],
            'sub' => $extra['sub'] ?? null,
            'from' => Currencies::format($extra['price'] ?? 0, $extra['currency'] ?? 'KES'),
        ];
    }

    private function duration(int $minutes): string
    {
        return $minutes >= 60
            ? rtrim(rtrim(number_format($minutes / 60, 1), '0'), '.').' h'
            : $minutes.' min';
    }
}
