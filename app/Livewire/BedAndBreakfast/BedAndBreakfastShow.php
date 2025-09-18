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
        if (!$bnb->is_active || !$bnb->is_verified) {
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
            $this->isWishlisted = $this->bnb->isWishlistedBy(Auth::user());
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

        Auth::user()->wishlistBnBs()->toggle($this->bnb->id);
        $this->isWishlisted = !$this->isWishlisted;
    }

    /**
     * Infinite scroll / load more reviews
     */
    public function loadReviews(): void
    {
        $perPage = 5;

        $query = $this->bnb->reviews()
            ->with('guest')
            ->latest()
            ->skip(($this->reviewPage - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        if ($query->count() > $perPage) {
            $this->hasMoreReviews = true;
            $query = $query->take($perPage);
        } else {
            $this->hasMoreReviews = false;
        }

        $this->reviews = $this->reviews->merge($query);
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
