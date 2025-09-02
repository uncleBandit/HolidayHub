<?php

namespace App\Livewire\Activity;

use Livewire\Component;
use App\Models\Activity;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ActivityShow extends Component
{
    public Activity $activity;
    public $reviews;
    public $availability = [];
    public bool $isWishlisted = false;
    public int $reviewPage = 1;

    public function mount($slug)
    {
        // Load activity with relationships
        $this->activity = Activity::with(['images', 'activityAmenities', 'schedules'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Load initial reviews
        $this->reviews = $this->activity->reviews()
            ->latest()
            ->take(5)
            ->get();

        // Load availability (e.g., for next month)
        $this->availability = $this->activity->getAvailabilityForNextMonth();

        // Wishlist state
        if (Auth::check()) {
            $this->isWishlisted = Auth::user()
                ?->wishlistActivities()
                ?->where('activity_id', $this->activity->id)
                ->exists() ?? false;
        }
    }

    public function toggleWishlist()
    {
        if (!Auth::check()) {
            $this->dispatch('authRequired'); // Trigger login modal
            return;
        }

        Auth::user()->wishlistActivities()->toggle($this->activity->id);
        $this->isWishlisted = !$this->isWishlisted;
    }

    public function loadMoreReviews()
    {
        $this->reviewPage++;

        $moreReviews = $this->activity->reviews()
            ->latest()
            ->skip(($this->reviewPage - 1) * 5)
            ->take(5)
            ->get();

        $this->reviews = $this->reviews->merge($moreReviews);
    }

    public function bookNow($scheduleId)
    {
        $this->dispatch('openBookingModal', [
            'activityId' => $this->activity->id,
            'scheduleId' => $scheduleId,
        ]);
    }

    public function render()
    {
        return view('livewire.activity.activity-show');
    }
}
