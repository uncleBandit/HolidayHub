<?php

namespace App\Livewire\Package;

use App\Models\Package;
use App\Models\Booking;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PackageShow extends Component
{
    use WithPagination;

    public Package $package;

    // UI State
    public string $activeTab = 'overview';
    public bool $isWishlisted = false;
    public bool $isGalleryOpen = false;
    public ?int $activeImageId = null;

    // Booking Form
    public string $checkIn = '';
    public string $checkOut = '';
    public int $guests = 1;
    public ?float $calculatedPrice = null;
    public ?string $availabilityMessage = null;

    protected $queryString = ['activeTab'];

    public function mount(Package $package): void
    {
        $this->package = $package->load([
            'images',
            'features',
            'offers',
            'reviews.user',
            'seasonalRates',
            'agent',
            'destination',
        ]);

        if (Auth::check()) {
            $this->isWishlisted = Auth::user()
                ?->role
                ?->wishlistPackages()
                ?->where('package_id', $this->package->id)
                ->exists() ?? false;
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
        if (!Auth::check()) {
            $this->dispatch('authRequired');
            return;
        }

        Auth::user()->wishlistPackages()->toggle($this->package->id);
        $this->isWishlisted = !$this->isWishlisted;
    }

    public function updated($property): void
    {
        if (in_array($property, ['checkIn', 'checkOut', 'guests'])) {
            $this->checkAvailability();
        }
    }

    public function checkAvailability(): void
    {
        if (!$this->checkIn || !$this->checkOut) {
            $this->availabilityMessage = null;
            $this->calculatedPrice = null;
            return;
        }

        $checkInDate = Carbon::parse($this->checkIn);
        $checkOutDate = Carbon::parse($this->checkOut);

        if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
            $this->availabilityMessage = "Check-out must be after check-in.";
            return;
        }

        if ($this->guests > $this->package->max_guests) {
            $this->availabilityMessage = "This package allows up to {$this->package->max_guests} guests.";
            return;
        }

        $isAvailable = $this->package->isAvailable(
            $checkInDate->toDateString(),
            $checkOutDate->toDateString()
        );

        if (!$isAvailable) {
            $this->availabilityMessage = "Sorry, this package is not available for the selected dates.";
            $this->calculatedPrice = null;
            return;
        }

        $this->calculatedPrice = $this->package->calculateDynamicPrice(
            $checkInDate,
            $checkOutDate,
            $this->guests
        );

        $this->availabilityMessage = "Great choice! This package is available.";
    }

    public function bookPackage(): void
    {
        if (!$this->calculatedPrice) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Please check availability before booking.',
            ]);
            return;
        }

        if (!Auth::check()) {
            $this->dispatch('authRequired');
            return;
        }

        $booking = Booking::create([
            'bookable_type' => Package::class,
            'bookable_id'   => $this->package->id,
            'guest_id'      => auth()->id(),
            'check_in'      => $this->checkIn,
            'check_out'     => $this->checkOut,
            'guests'        => $this->guests,
            'total_price'   => $this->calculatedPrice,
            'status'        => 'pending',
        ]);

        $this->dispatch('bookingPlaced', ['bookingId' => $booking->id]);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Your package booking request has been placed!',
        ]);

        $this->reset(['checkIn', 'checkOut', 'guests', 'calculatedPrice', 'availabilityMessage']);
    }

    public function render()
    {
        return view('livewire.package.package-show', [
            'reviews' => Review::where('reviewable_type', Package::class)
                ->where('reviewable_id', $this->package->id)
                ->with('user')
                ->latest()
                ->paginate(5),
            'offers' => $this->package->offers()->active()->get(),
        ]);
    }
}
