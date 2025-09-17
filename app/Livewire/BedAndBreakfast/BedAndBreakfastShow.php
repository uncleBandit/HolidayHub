<?php

namespace App\Livewire\BedAndBreakfast;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BedAndBreakfast;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Contracts\Bookable;

class BedAndBreakfastShow extends Component
{
    use WithPagination;

    public BedAndBreakfast $bnb;

    // UI state
    public string $activeTab = 'overview';
    public int $perPage = 5;
    public bool $isWishlisted = false;

    // Booking form state
    public string $checkIn = '';
    public string $checkOut = '';
    public int $guests = 1;
    public ?float $calculatedPrice = null;
    public ?string $availabilityMessage = null;

    // Reviews
    public $reviews;
    public int $reviewPage = 1;
    public bool $hasMoreReviews = true;

    protected $queryString = ['activeTab'];

    public function mount(BedAndBreakfast $bnb): void
    {
        $this->bnb = $bnb->load([
            'amenities',
            'features',
            'offers',
            'reviews.guest',
            'availabilities',
            'seasonalRates',
        ]);

        $this->reviews = Review::with('guest')
            ->where('reviewable_type', BedAndBreakfast::class)
            ->where('reviewable_id', $this->bnb->id)
            ->latest()
            ->take(5)
            ->get();

        // wishlist check
        if (Auth::check()) {
            $this->isWishlisted = Auth::user()
                ?->role
                ?->wishlistBnBs()
                ?->where('bed_and_breakfast_id', $this->bnb->id)
                ->exists() ?? false;
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
     * Check availability and calculate pricing
     */
    public function checkAvailability(): void
    {
        $this->validate([
            'checkIn' => 'required|date|after_or_equal:today',
            'checkOut' => 'required|date|after:checkIn',
            'guests' => 'required|integer|min:1|max:' . $this->bnb->max_guests,
        ]);

        $available = $this->bnb->isAvailable($this->checkIn, $this->checkOut);

        if (!$available) {
            $this->availabilityMessage = "Sorry, not available for those dates.";
            $this->calculatedPrice = null;
            return;
        }

        $nights = Carbon::parse($this->checkIn)->diffInDays(Carbon::parse($this->checkOut));

        // Apply seasonal rates, offers, or just base price
        $this->calculatedPrice = $this->bnb->getPriceForDate($this->checkIn) * $nights;

        $this->availabilityMessage = "Good news! Available for {$nights} nights.";
    }

    /**
     * Proceed to booking (trigger modal or create draft booking)
     */
    public function bookNow(): void
    {
        if (!$this->calculatedPrice) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Check availability before booking.'
            ]);
            return;
        }

        $this->dispatch('openBookingModal', [
            'bnbId'    => $this->bnb->id,
            'checkIn'  => $this->checkIn,
            'checkOut' => $this->checkOut,
            'guests'   => $this->guests,
            'price'    => $this->calculatedPrice,
        ]);
    }

    /**
     * Infinite scroll reviews loader
     */
    public function loadReviews(): void
    {
        $perPage = 5;

        $query = Review::with('user')
            ->where('reviewable_type', BedAndBreakfast::class)
            ->where('reviewable_id', $this->bnb->id)
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
