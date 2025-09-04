<?php

namespace App\Livewire\Hotel;

use Livewire\Component;
use App\Models\Hotel;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class HotelShow extends Component
{
    public Hotel $hotel;
    public $reviews;
    public $availability = [];
    public bool $isWishlisted = false;

    public $isGalleryOpen = false;
    public $activeImageId;

    public function showGallery($imageId)
    {
        $this->isGalleryOpen = true;
        $this->activeImageId = $imageId;
    }

    public function closeGallery()
    {
        $this->isGalleryOpen = false;
        $this->activeImageId = null;
    }

    public function mount($slug)
    {
        $this->hotel = Hotel::with(['images', 'hotelAmenities', 'rooms'])->where('slug', $slug)->firstOrFail();
        $this->reviews = Review::where('reviewable_type', Hotel::class)
            ->where('reviewable_id', $this->hotel->id)
            ->latest()
            ->take(5)
            ->get();

        $this->isWishlisted = false;

        if (Auth::check()) {
        $this->isWishlisted = Auth::user()
            ?->role
            ?->wishlistHotels()
            ?->where('hotel_id', $this->hotel->id)
            ->exists() ?? false; // The `?? false` ensures a boolean value is always assigned.
        }


        // Example: load availability from a service
        $this->availability = $this->hotel->getAvailabilityForNextMonth();
    }

    public function toggleWishlist()
    {
        if (!Auth::check()) {
            $this->dispatch('authRequired'); // trigger login modal
            return;
        }

        Auth::user()->wishlistHotels()->toggle($this->hotel->id);
        $this->isWishlisted = !$this->isWishlisted;
    }

    public function bookNow($roomId)
    {
        $this->dispatch('openBookingModal', [
            'hotelId' => $this->hotel->id,
            'roomId' => $roomId,
        ]);
    }

    public int $reviewPage = 1;
    public bool $hasMoreReviews = true;

    public function loadReviews()
    {
    $perPage = 5;

    $query = Review::where('reviewable_type', Hotel::class)
        ->where('reviewable_id', $this->hotel->id)
        ->latest()
        ->skip(($this->reviewPage - 1) * $perPage)
        ->take($perPage + 1) // fetch one extra to check if more exist
        ->get();

    if ($query->count() > $perPage) {
        $this->hasMoreReviews = true;
        $query = $query->take($perPage);
    } else {
        $this->hasMoreReviews = false;
    }

    // Merge new reviews without overwriting existing ones
    $this->reviews = $this->reviews->merge($query);

    // Increment page for next call
    $this->reviewPage++;
    }


    public function render()
    {
        return view('livewire.hotel.hotel_show');
    }
}
