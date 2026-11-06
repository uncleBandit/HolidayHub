<?php

namespace App\Modules\Packages\Application\Services;

use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Packages\Domain\Models\Package;
use App\Shared\Support\Currencies;
use App\Shared\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Live packages for the small "journeys" rails around the site: on a stay or
 * experience page (journeys that include it), on a destination page and in
 * the For You feed. Each comes back as a ready-to-render card array.
 */
class PackageDiscovery
{
    private const FALLBACK = 'https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=900&q=80';

    /** Live journeys that include this hotel (any room type), villa, B&B or experience. */
    public function including(Model $listing, int $limit = 6): Collection
    {
        return $this->cards(Package::published()->including($listing), $limit);
    }

    /** Live journeys that go to a destination (as the main one or a stop). */
    public function forDestination(Destination $destination, int $limit = 8): Collection
    {
        return $this->cards(
            Package::published()->where(fn ($q) => $q
                ->where('destination_id', $destination->id)
                ->orWhereJsonContains('meta_data->destinations', $destination->id)),
            $limit
        );
    }

    /** Featured, then newest, live journeys. */
    public function featured(int $limit = 8): Collection
    {
        return $this->cards(Package::published()->orderByDesc('is_featured'), $limit);
    }

    private function cards(Builder $query, int $limit): Collection
    {
        return $query
            ->with(['destination', 'agent'])
            ->withCount(['components as listings_count' => fn ($q) => $q->whereIn('kind', ['stay', 'experience'])])
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'hook' => $package->short_description,
                'url' => route('packages-show', $package->slug),
                'image' => Media::url($package->cover_image) ?: self::FALLBACK,
                'film' => Media::url($package->cover_video),
                'place' => $package->destination?->name,
                'days' => (int) $package->duration_days,
                'listings' => (int) $package->listings_count,
                'agency' => $package->agent?->agency_name,
                'from' => (float) $package->base_price > 0 ? Currencies::format($package->base_price, $package->getCurrency()) : null,
            ]);
    }
}
