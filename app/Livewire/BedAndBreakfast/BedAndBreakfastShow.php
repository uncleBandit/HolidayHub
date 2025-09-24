<?php

namespace App\Livewire\BedAndBreakfast;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BedAndBreakfast;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class BedAndBreakfastShow extends Component
{
    use WithPagination;

    public BedAndBreakfast $bnb;

    // UI state
    public string $activeTab = 'overview';
    public bool $isWishlisted = false;
    public int $guests = 1;

    // Reviews
    public $reviews;
    public int $reviewPage = 1;
    public bool $hasMoreReviews = true;

    protected $queryString = ['activeTab'];

    public function mount(BedAndBreakfast $bnb): void
    {
         // Hide inactive for everyone
        if (!$bnb->is_active) {
            abort(404);
        }

        // If not verified, only the provider who created it can see
        if (
            !$bnb->is_verified &&
            (!Auth::check() || Auth::id() !== $bnb->provider_id)
        ) {
            abort(404, 'This Bed & Breakfast is not available.');
        }

        $this->bnb = $bnb->load([
            'amenities',
            'features',
            'offers',
            'reviews.guest',
            'availabilities',
            'seasonalRates',
        ]);

        // preload reviews
        $this->reviews = $this->bnb->reviews()
            ->with('guest')
            ->latest()
            ->take(5)
            ->get();

        // wishlist check
        if (Auth::check()) {
            $this->isWishlisted = Auth::check() && Auth::user()
                ->wishlists()
                ->where('wishlistable_type', BedAndBreakfast::class)
                ->where('wishlistable_id', $this->bnb->id)
                ->exists();

        }
    }


    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function toggleWishlist(): void
    {
    if (!Auth::check()) {
        $this->dispatch('authRequired');
        return;
    }

    $user = Auth::user();

    $existing = $user->wishlists()
        ->where('wishlistable_type', BedAndBreakfast::class)
        ->where('wishlistable_id', $this->bnb->id)
        ->first();

    if ($existing) {
        $existing->delete();
        $this->isWishlisted = false;
    } else {
        $user->wishlists()->create([
            'wishlistable_id'   => $this->bnb->id,
            'wishlistable_type' => BedAndBreakfast::class,
            'priority'          => 'medium', // default if you want
        ]);
        $this->isWishlisted = true;
    }
    }


     /**
     * Infinite scroll / load more reviews
     */
    public function loadReviews(): void
    {
        $perPage = 5;

        // Fetch new reviews for the current page
        $newReviews = $this->bnb->reviews()
            ->with('guest')
            ->latest()
            ->forPage($this->reviewPage, $perPage)
            ->get();

        // Append the new reviews to the existing collection
        if (is_null($this->reviews)) {
            $this->reviews = collect();
        }

        $this->reviews = $this->reviews->merge($newReviews);

        // Check if there are more reviews to load
        $this->hasMoreReviews = ($newReviews->count() === $perPage);
        $this->reviewPage++;
    }

    public function render()
    {
        return view('livewire.bed-and-breakfast.bed-and-breakfast-show', [
            'reviews' => $this->reviews,
            'offers' => $this->bnb->offers()->active()->get(),
        ]);
    }
}
