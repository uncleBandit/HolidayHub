<?php

namespace App\Modules\Accommodation\Presentation\Livewire\BedAndBreakfast;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

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
        $bnb->loadMissing('accommodation');
        $user = Auth::user();
        $providerId = $user?->provider?->id;
        $isOwner = $providerId !== null
            && (int) $providerId === (int) $bnb->accommodation?->provider_id;
        abort_unless($bnb->accommodation?->isPublished() || $isOwner || $user?->isPlatformAdmin(), 404);

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
        if (! Auth::check()) {
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
                'wishlistable_id' => $this->bnb->id,
                'wishlistable_type' => BedAndBreakfast::class,
                'priority' => 'medium', // default if you want
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
