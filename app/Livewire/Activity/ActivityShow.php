<?php

namespace App\Livewire\Activity;

use Livewire\Component;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityShow extends Component
{
    public Activity $activity;

    /** @var array<int, array<string,mixed>> */
    public array $reviews = [];

    /** @var array<int, mixed> */
    public array $availability = [];

    public int $reviewPage = 1;
    public bool $hasMoreReviews = false;
    public array $selectedSchedule = [];


    // Wishlist support (optional)
    // public bool $isWishlisted = false;

    private int $reviewsPerPage = 5;

    public function mount($activity)
    {
        $this->activity = Activity::with(['images', 'amenities'])
            ->where('slug', $activity->slug)
            ->firstOrFail();

        $this->loadInitialReviews();
        $this->loadAvailability();

        // $this->setWishlistState();
    }

    /**
     * Load the first batch of reviews.
     */
    private function loadInitialReviews(): void
    {
        $allReviews = $this->activity->reviews()->latest();
        $this->hasMoreReviews = $allReviews->count() > $this->reviewsPerPage;

        $this->reviews = $allReviews
            ->take($this->reviewsPerPage)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'user_name' => $r->user->name ?? 'Guest',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Lazy-load more reviews on demand.
     */
    public function loadMoreReviews(): void
    {
        $this->reviewPage++;

        $moreReviews = $this->activity->reviews()
            ->latest()
            ->skip(($this->reviewPage - 1) * $this->reviewsPerPage)
            ->take($this->reviewsPerPage)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'user_name' => $r->user->name ?? 'Guest',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
            ])
            ->toArray();

        $this->reviews = array_merge($this->reviews, $moreReviews);

        // Check if more reviews are available
        $totalReviews = $this->activity->reviews()->count();
        $this->hasMoreReviews = count($this->reviews) < $totalReviews;
    }

    /**
     * Load availability data for display (e.g., next month).
     */
    private function loadAvailability(): void
    {
        $this->availability = $this->activity->availabilities
            ->map(fn($a) => [
                'id' => $a->id,
                'start_date' => $a->start_date,
                'end_date' => $a->end_date,
                'slots' => $a->slots ?? 0,
            ])
            ->toArray() ?? [];
    }

    /**
     * Trigger booking modal.
     */
    public function bookNow(?int $scheduleId = null)
    {
        if (!$scheduleId) {
            session()->flash('error', 'No schedule selected!');
            return;
        }

        $schedule = $this->activity->availabilities->find($scheduleId);

        if (!$schedule) {
            session()->flash('error', 'Invalid schedule selected!');
            return;
        }

        $this->selectedSchedule = $schedule->toArray();
        session()->flash('success', "You selected schedule #{$scheduleId} for booking!");
    }


    /**
     * Optional: Set wishlist state for the current user.
     */
    // private function setWishlistState(): void
    // {
    //     if (Auth::check()) {
    //         $this->isWishlisted = Auth::user()
    //             ->wishlistActivities()
    //             ->where('activity_id', $this->activity->id)
    //             ->exists();
    //     }
    // }

    /**
     * Optional: Toggle wishlist for authenticated user.
     */
    // public function toggleWishlist(): void
    // {
    //     if (!Auth::check()) {
    //         $this->dispatch('authRequired');
    //         return;
    //     }
    //     Auth::user()->wishlistActivities()->toggle($this->activity->id);
    //     $this->isWishlisted = !$this->isWishlisted;
    // }

    public function render()
    {
        return view('livewire.activity.activity-show');
    }
}
