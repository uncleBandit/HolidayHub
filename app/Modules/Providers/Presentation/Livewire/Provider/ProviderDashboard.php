<?php

namespace App\Modules\Providers\Presentation\Livewire\Provider;

use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Catalog\Domain\Models\Offer;
use App\Modules\Catalog\Presentation\Livewire\Forms\AmenityForm;
use App\Modules\Catalog\Presentation\Livewire\Forms\OfferForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProviderDashboard extends Component
{
    use WithPagination;

    // Form Objects
    public OfferForm $offerForm;

    public AmenityForm $amenityForm;

    // Pagination
    public int $perPage = 10;

    // Filters & search
    public string $searchOffer = '';

    public string $searchAmenity = '';

    // Modals
    public bool $showOfferModal = false;

    public bool $showAmenityModal = false;

    // Listeners for updates
    #[On('refreshDashboard')]
    public function refresh(): void
    {
        // Trigger re-render
    }

    // Reset pagination when search changes
    public function updated(string $property): void
    {
        if (in_array($property, ['searchOffer', 'searchAmenity'])) {
            $this->resetPage();
        }
    }

    // --- Offer Methods ---
    public function openOfferModal(?Offer $offer = null): void
    {
        $this->resetValidation();
        $this->offerForm->setOffer($offer);
        $this->showOfferModal = true;
    }

    public function saveOffer(): void
    {
        $this->offerForm->save();
        $this->showOfferModal = false;
        $this->dispatch('success', 'Offer saved successfully.');
        $this->dispatch('refreshDashboard');
    }

    public function deleteOffer(Offer $offer): void
    {
        $offer->delete();
        $this->dispatch('success', 'Offer deleted successfully.');
        $this->dispatch('refreshDashboard');
    }

    // --- Amenity Methods ---
    public function openAmenityModal(?Amenity $amenity = null): void
    {
        $this->resetValidation();
        $this->amenityForm->setAmenity($amenity);
        $this->showAmenityModal = true;
    }

    public function saveAmenity(): void
    {
        $this->amenityForm->save();
        $this->showAmenityModal = false;
        $this->dispatch('success', 'Amenity saved successfully.');
        $this->dispatch('refreshDashboard');
    }

    public function deleteAmenity(Amenity $amenity): void
    {
        $amenity->delete();
        $this->dispatch('success', 'Amenity deleted successfully.');
        $this->dispatch('refreshDashboard');
    }

    public function render()
    {
        $provider = Auth::user()->provider;

        $accommodations = $provider->accommodations()
            ->with('bookable')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();

        // later in blade or processing:
        foreach ($accommodations as $acc) {
            $avgRoomPrice = null;

            if (method_exists($acc->bookable, 'rooms')) {
                $avgRoomPrice = $acc->bookable->rooms()->avg('price_per_night');
            }

            $acc->avg_room_price = $avgRoomPrice;
        }

        // Offers with optional search
        $offers = $provider->offers()
            ->with('destination')
            ->when($this->searchOffer, fn ($q) => $q->where('title', 'like', "%{$this->searchOffer}%"))
            ->latest()
            ->paginate($this->perPage);

        $services = $provider->services()
            ->when($this->searchService ?? '', fn ($q) => $q->where('name', 'like', "%{$this->searchService}%"))
            ->latest()
            ->get(); // or ->paginate($this->perPage) if you want pagination

        // Amenities with optional search
        $amenities = Amenity::query()
            ->when($this->searchAmenity, fn ($q) => $q->where('name', 'like', "%{$this->searchAmenity}%"))
            ->latest()
            ->paginate($this->perPage);

        // Flatten all rooms for the list
        $rooms = $offers->flatMap(fn ($offer) => $offer->rooms);

        // Calculate stats safely
        $stats = [
            'totalRooms' => $offers->sum('rooms_count') ?? 0,      // Make sure 'rooms_count' exists
            'totalServices' => $offers->count() ?? 0,
            'confirmedBookings' => $offers->sum(function ($offer) {
                return $offer->bookings()->where('status', 'confirmed')->count();
            }),
            'pendingBookings' => $offers->sum(function ($offer) {
                return $offer->bookings()->where('status', 'pending')->count();
            }),
            'cancelledBookings' => $offers->sum(function ($offer) {
                return $offer->bookings()->where('status', 'cancelled')->count();
            }),
            'todayRevenue' => $offers->sum(function ($offer) {
                return $offer->bookings()->whereDate('created_at', now())->sum('total_amount');
            }),
        ];

        // Metrics
        $bookingsCount = $provider->offers()->withCount('bookings')->get()->sum('bookings_count');
        $totalRevenue = $provider->offers()->withSum('bookings', 'total_amount')->get()->sum('bookings_sum_total_amount');

        $bookings = Booking::whereHas('offer', function ($query) use ($provider) {
            $query->where('provider_id', $provider->id);
        })->latest()->paginate(10);

        return view('livewire.provider.provider-dashboard', compact(
            'provider',
            'offers',
            'amenities',
            'bookingsCount',
            'totalRevenue',
            'stats',
            'rooms',
            'services',
            'bookings'
        ));
    }
}
