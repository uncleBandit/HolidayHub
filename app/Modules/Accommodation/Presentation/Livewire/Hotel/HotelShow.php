<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Hotel;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Reviews\Domain\Models\Review;
use App\Shared\Domain\Contracts\Bookable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

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

    public $bookingData = [];

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

    #[On('bookingClosed')]
    public function resetBooking()
    {
        // $this->showBookingForm = false;
        $this->bookingData = [];
    }

    public function mount(Hotel $hotel) // 👈 accept Hotel directly
    {
        // Eager load relationships immediately
        $this->hotel = $hotel->load([
            'images',
            'amenities',
            'roomTypes.rooms',
            'reviews.guest',
        ]);

        $this->reviews = Review::with('guest')
            ->where('reviewable_type', Hotel::class)
            ->where('reviewable_id', $this->hotel->id)
            ->latest()
            ->take(5)
            ->get();

        if (Auth::check()) {
            $this->isWishlisted = Auth::check() && Auth::user()
                ->wishlists()
                ->where('wishlistable_type', Hotel::class)
                ->where('wishlistable_id', $this->hotel->id)
                ->exists();

        }

        // Group rooms by their room_type
        $this->roomTypes = RoomType::whereIn(
            'id',
            $this->hotel->rooms->pluck('room_type_id')->unique()
        )->get();

        $this->availability = $this->getMonthlyAvailability($this->hotel);
    }

    public function toggleWishlist(): void
    {
        if (! Auth::check()) {
            $this->dispatch('authRequired');

            return;
        }

        $user = Auth::user();

        $existing = $user->wishlists()
            ->where('wishlistable_type', Hotel::class)
            ->where('wishlistable_id', $this->hotel->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->isWishlisted = false;
        } else {
            $user->wishlists()->create([
                'wishlistable_id' => $this->hotel->id,
                'wishlistable_type' => Hotel::class,
                'priority' => 'medium', // default if you want
            ]);
            $this->isWishlisted = true;
        }
    }

    // public function bookNow($roomId)
    // {
    // if ($this->showBookingForm) {
    //      return; // prevent re-trigger if already open
    //  }

    //  $this->dispatch('openBookingModal', [
    //      'hotelId' => $this->hotel->id,
    //     'roomId' => $roomId,
    // ]);
    // }

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

        $query = Review::with('guest') // ✅ eager load here
            ->where('reviewable_type', Hotel::class)
            ->where('reviewable_id', $this->hotel->id)
            ->latest()
            ->skip($this->reviewPage * $perPage)
            ->take($perPage + 1)
            ->get();

        if ($query->count() > $perPage) {
            $this->hasMoreReviews = true;
            $query = $query->take($perPage);
        } else {
            $this->hasMoreReviews = false;
        }

        // ✅ merge without triggering lazy loading
        $this->reviews = $this->reviews->merge($query);

        $this->reviewPage++;
    }

    public function render()
    {
        // Make sure every review has guest
        $this->reviews->load('guest');

        return view('livewire.hotel.hotel_show');
    }
}
