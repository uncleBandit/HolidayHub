<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Villa;

use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Reviews\Domain\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VillaShow extends Component
{
    use WithPagination;

    public Villa $villa;

    public string $activeTab = 'overview';

    // Booking form state
    public string $checkIn = '';

    public string $checkOut = '';

    public int $guests = 1;

    public ?float $calculatedPrice = null;

    public ?string $availabilityMessage = null;

    // UI state
    public bool $isWishlisted = false;

    public bool $isGalleryOpen = false;

    public ?int $activeImageId = null;

    // Reviews
    public int $perPage = 5;

    protected $queryString = ['activeTab'];

    public function mount(Villa $villa): void
    {
        $villa->loadMissing('accommodation');
        $user = Auth::user();
        $providerId = $user?->provider?->id;
        $isOwner = $providerId !== null
            && (int) $providerId === (int) $villa->accommodation?->provider_id;
        abort_unless($villa->accommodation?->isPublished() || $isOwner || $user?->isPlatformAdmin(), 404);

        $this->villa = $villa->load([

            'amenities',
            'reviews.user',
            'provider',
            'seasonalRates',
        ]);

        if (Auth::check()) {
            $this->isWishlisted = Auth::check() && Auth::user()
                ->wishlists()
                ->where('wishlistable_type', Villa::class)
                ->where('wishlistable_id', $this->villa->id)
                ->exists();

        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function showGallery(int $imageId): void
    {
        $this->isGalleryOpen = true;
        $this->activeImageId = $imageId;
    }

    public function closeGallery(): void
    {
        $this->isGalleryOpen = false;
        $this->activeImageId = null;
    }

    public function toggleWishlist(): void
    {
        if (! Auth::check()) {
            $this->dispatch('authRequired');

            return;
        }

        $user = Auth::user();

        $existing = $user->wishlists()
            ->where('wishlistable_type', Villa::class)
            ->where('wishlistable_id', $this->villa->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $this->isWishlisted = false;
        } else {
            $user->wishlists()->create([
                'wishlistable_id' => $this->villa->id,
                'wishlistable_type' => Villa::class,
                'priority' => 'medium', // default if you want
            ]);
            $this->isWishlisted = true;
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['checkIn', 'checkOut', 'guests'])) {
            $this->checkAvailability();
        }
    }

    public function checkAvailability(): void
    {
        if (! $this->checkIn || ! $this->checkOut) {
            $this->availabilityMessage = null;
            $this->calculatedPrice = null;

            return;
        }

        $checkInDate = Carbon::parse($this->checkIn);
        $checkOutDate = Carbon::parse($this->checkOut);

        if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
            $this->availabilityMessage = 'Check-out must be after check-in.';
            $this->calculatedPrice = null;

            return;
        }

        // Max guests check
        if ($this->guests > $this->villa->max_guests) {
            $this->availabilityMessage = "This villa accommodates up to {$this->villa->max_guests} guests.";
            $this->calculatedPrice = null;

            return;
        }

        $isAvailable = $this->villa->isAvailable(
            $checkInDate->toDateString(),
            $checkOutDate->toDateString()
        );

        if (! $isAvailable) {
            $this->availabilityMessage = 'Sorry, this villa is not available for the selected dates.';
            $this->calculatedPrice = null;

            return;
        }

        $days = $checkInDate->diffInDays($checkOutDate);
        $this->calculatedPrice = $this->villa->calculateDynamicPrice(
            $checkInDate,
            $checkOutDate,
            $this->guests
        );

        $this->availabilityMessage = 'Good news! This villa is available.';
    }

    public function bookVilla(): void
    {
        if (! $this->calculatedPrice) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Please check availability before booking.',
            ]);

            return;
        }

        if (! Auth::check()) {
            $this->dispatch('authRequired');

            return;
        }

        $booking = Booking::create([
            'bookable_type' => Villa::class,
            'bookable_id' => $this->villa->id,
            'guest_id' => auth()->id(),
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'guests' => $this->guests,
            'total_price' => $this->calculatedPrice,
            'status' => 'pending',
        ]);

        // 👉 You can dispatch event to redirect to a payment flow
        $this->dispatch('bookingPlaced', ['bookingId' => $booking->id]);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Your booking request has been placed!',
        ]);

        $this->reset(['checkIn', 'checkOut', 'guests', 'calculatedPrice', 'availabilityMessage']);
    }

    public function render()
    {
        return view('livewire.villa.villa-show', [
            'villa' => $this->villa,
            'reviews' => Review::where('reviewable_type', Villa::class)
                ->where('reviewable_id', $this->villa->id)
                ->with('user')
                ->latest()
                ->paginate($this->perPage),
        ]);
    }
}
