<?php

namespace App\Livewire\Hotel;

use Livewire\Component;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\RoomType;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Contracts\Interface\Bookable;
use App\Models\Room;

class HotelShow extends Component
{
    public Hotel $hotel;
    public $reviews;
    public ?RoomType $selectedRoomType = null;
    public $availability = [];
    public bool $isWishlisted = false;

    public $isGalleryOpen = false;
    public $activeImageId;
    public $roomTypes;

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
        $this->hotel = Hotel::with(['images', 'amenities', 'roomTypes.rooms','reviews.guest'])->where('slug', $slug)->firstOrFail();
        $this->reviews = Review::with('guest') // 👈 eager load guest here too
        ->where('reviewable_type', Hotel::class)
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

        // Group rooms by their room_type to get a collection of unique types
        $this->roomTypes = RoomType::whereIn(
                'id',
                $this->hotel->rooms->pluck('room_type_id')->unique()
            )->get();


        $this->availability = $this->getMonthlyAvailability($this->hotel);
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

    public function selectRoomType($roomTypeId)
    {
        $this->selectedRoomType = $this->hotel->roomTypes->find($roomTypeId);
    }

    private function getMonthlyAvailability(Bookable $bookable): array
    {
        $start = Carbon::today();
        $end = $start->copy()->addMonth();

        $availability = [];
        $date = $start->copy();

        while ($date->lessThan($end)) {
            $day = $date->toDateString();

            // Check the availability of the entire hotel on this day.
            // This assumes the Hotel's isAvailable method checks all its rooms.
            $availability[$day] = $bookable->isAvailable($day, $date->copy()->addDay()->toDateString());

            $date->addDay();
        }

        return $availability;
    }

    public int $reviewPage = 1;
    public bool $hasMoreReviews = true;

    public function loadReviews()
    {
    $perPage = 5;

    $query = Review::with(['guest'])
        ->where('reviewable_type', Hotel::class)
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
