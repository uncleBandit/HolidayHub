<?php

namespace App\Services\Hotels;

use App\Models\Hotel;
use Illuminate\Support\Facades\Storage;

class HotelManager
{
    public function getHotels(array $filters)
    {
        $query = Hotel::query();

        if (!empty($filters['location'])) {
            $query->where('location', 'like', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['min_price'])) {
            $query->where('price_per_night', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price_per_night', '<=', $filters['max_price']);
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', $filters['rating']);
        }

        // Future: availability filter using bookings

        return $query->with(['rooms', 'reviews'])->paginate(10);
    }

    public function create(array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('hotels', 'public');
        }

        return Hotel::create($data);
    }

    public function find(int $id)
    {
        return Hotel::with(['rooms', 'reviews'])->findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $hotel = Hotel::findOrFail($id);

        if (isset($data['image'])) {
            if ($hotel->image) {
                Storage::disk('public')->delete($hotel->image);
            }
            $data['image'] = $data['image']->store('hotels', 'public');
        }

        $hotel->update($data);

        return $hotel;
    }

    public function delete(int $id)
    {
        $hotel = Hotel::findOrFail($id);

        if ($hotel->image) {
            Storage::disk('public')->delete($hotel->image);
        }

        $hotel->delete();
    }



    public function findBySlug(string $slug)
    {
        return Hotel::with(['rooms', 'reviews'])
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
