<?php

namespace App\Http\Livewire\Provider;

use App\Livewire\Forms\AmenityForm;
use App\Livewire\Forms\PackageForm;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use App\Models\Package;
use App\Models\Amenity;

class ProviderDashboard extends Component
{
    use WithPagination;

    // Form Objects
    public PackageForm $packageForm;
    public AmenityForm $amenityForm;

    // Pagination
    public int $perPage = 10;

    // Filters & search
    public string $searchPackage = '';
    public string $searchAmenity = '';

    // Modals
    public bool $showPackageModal = false;
    public bool $showAmenityModal = false;

    // Listeners for updates
    #[On('refreshDashboard')]
    public function refresh()
    {
        // This method does nothing, but the #[On] attribute triggers a re-render.
    }

    // Reset pagination when search changes
    public function updated(string $property): void
    {
        if (in_array($property, ['searchPackage', 'searchAmenity'])) {
            $this->resetPage();
        }
    }

    // --- Package Methods ---
    public function openPackageModal(?Package $package = null): void
    {
        $this->resetValidation();
        $this->packageForm->setPackage($package);
        $this->showPackageModal = true;
    }

    public function savePackage(): void
    {
        $this->packageForm->save();
        $this->showPackageModal = false;
        $this->dispatch('success', 'Package saved successfully.');
        $this->dispatch('refreshDashboard');
    }

    public function deletePackage(Package $package): void
    {
        $package->delete();
        $this->dispatch('success', 'Package deleted successfully.');
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

        $packages = $provider->packages()
            ->with('destination')
            ->when($this->searchPackage, fn($q) => $q->where('title', 'like', "%{$this->searchPackage}%"))
            ->latest()
            ->paginate($this->perPage);

        $amenities = Amenity::query()
            ->when($this->searchAmenity, fn($q) => $q->where('name', 'like', "%{$this->searchAmenity}%"))
            ->latest()
            ->paginate($this->perPage);

        // Optimized queries for total counts and revenue
        $bookingsCount = $provider->packages()->withCount('bookings')->get()->sum('bookings_count');
        $totalRevenue = $provider->packages()->withSum('bookings', 'total_price')->get()->sum('bookings_sum_total_price');

        return view('livewire.provider.provider-dashboard', compact(
            'provider',
            'packages',
            'amenities',
            'bookingsCount',
            'totalRevenue'
        ));
    }
}
