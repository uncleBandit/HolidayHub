<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\Hotel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HotelManager
{
    public function getHotels(array $filters, string $sort = 'latest', int $perPage = 15): LengthAwarePaginator
    {
        $query = Hotel::query()
            ->published()
            ->with(['rooms', 'reviews', 'accommodation'])
            ->whereHas('accommodation', function ($accommodation) use ($filters) {
                $accommodation->published()
                    ->when($filters['location'] ?? null, fn ($q, $location) => $q->where(function ($q) use ($location) {
                        $q->where('city', 'like', "%{$location}%")
                            ->orWhere('country', 'like', "%{$location}%");
                    }))
                    ->when($filters['price_min'] ?? null, fn ($q, $price) => $q->where('avg_price_per_night', '>=', $price))
                    ->when($filters['price_max'] ?? null, fn ($q, $price) => $q->where('avg_price_per_night', '<=', $price))
                    ->when($filters['rating'] ?? null, fn ($q, $rating) => $q->where('avg_rating', '>=', $rating));
            });

        match ($sort) {
            'price_low' => $query->orderBy(
                Accommodation::query()->select('avg_price_per_night')->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass())
            ),
            'price_high' => $query->orderByDesc(
                Accommodation::query()->select('avg_price_per_night')->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass())
            ),
            'rating' => $query->orderByDesc(
                Accommodation::query()->select('avg_rating')->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass())
            ),
            'name' => $query->orderBy(
                Accommodation::query()->select('name')->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass())
            ),
            default => $query->latest(),
        };

        return $query->paginate(min(max($perPage, 1), 100));
    }

    public function create(array $data): Hotel
    {
        $providerId = Auth::user()?->isPlatformAdmin()
            ? ($data['provider_id'] ?? null)
            : Auth::user()?->provider?->id;
        abort_unless($providerId, 403, 'A provider profile is required to create a hotel.');

        return DB::transaction(function () use ($data, $providerId): Hotel {
            $destinationId = $data['destination_id'];
            unset($data['destination_id'], $data['provider_id']);
            $data['provider_id'] = $providerId;
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(8));

            $hotel = Hotel::create($data);
            $hotel->accommodation()->update(['destination_id' => $destinationId]);

            return $hotel->fresh(['accommodation']);
        });
    }

    public function find(int $id): Hotel
    {
        return Hotel::with(['rooms', 'reviews', 'accommodation'])->findOrFail($id);
    }

    public function update(Hotel $hotel, array $data): Hotel
    {
        return DB::transaction(function () use ($hotel, $data): Hotel {
            $destinationId = $data['destination_id'] ?? null;
            unset($data['destination_id']);
            $hotel->update($data);

            if ($destinationId !== null) {
                $hotel->accommodation()->update(['destination_id' => $destinationId]);
            }

            return $hotel->fresh(['accommodation']);
        });
    }

    public function delete(Hotel $hotel): void
    {
        $hotel->delete();
    }

    public function findBySlug(string $slug): Hotel
    {
        return Hotel::published()->with(['rooms', 'reviews', 'accommodation'])
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
