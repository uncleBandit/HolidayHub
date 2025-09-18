<?php

namespace App\Livewire\BedAndBreakfast;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BedAndBreakfast;
use App\Models\Amenity;

class BedAndBreakfastIndex extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public ?string $city = null;
    public ?int $minPrice = null;
    public ?int $maxPrice = null;
    public array $selectedAmenities = [];
    public bool $onlyFeatured = false;

    // Sorting
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    // Pagination
    public int $perPage = 9;

    protected $queryString = [
        'search' => ['except' => ''],
        'city' => ['except' => null],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'selectedAmenities' => ['except' => []],
        'onlyFeatured' => ['except' => false],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
        'perPage' => ['except' => 9],
        'page' => ['except' => 1],
    ];

    public function updating($field)
    {
        // Reset page when changing filters
        if ($field !== 'page') {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $query = BedAndBreakfast::query()
            ->where('is_active', true)
            ->where('is_verified', true)
            ->with(['provider', 'amenities'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // 🔍 Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('city', 'like', "%{$this->search}%")
                  ->orWhere('country', 'like', "%{$this->search}%");
            });
        }

        // 🏙️ City
        if ($this->city) {
            $query->where('city', $this->city);
        }

        // 💵 Price range
        if ($this->minPrice) {
            $query->where('price_per_night', '>=', $this->minPrice);
        }

        if ($this->maxPrice) {
            $query->where('price_per_night', '<=', $this->maxPrice);
        }

        // 🌟 Featured
        if ($this->onlyFeatured) {
            $query->where('is_featured', true);
        }

        // 🛎️ Amenities
        if ($this->selectedAmenities) {
            $query->whereHas('amenities', function ($q) {
                $q->whereIn('amenities.id', $this->selectedAmenities);
            });
        }

        // 📊 Sorting options
        switch ($this->sortField) {
            case 'price':
                $query->orderBy('price_per_night', $this->sortDirection);
                break;
            case 'rating':
                $query->orderBy('reviews_avg_rating', $this->sortDirection);
                break;
            case 'newest':
                $query->orderBy('created_at', $this->sortDirection);
                break;
            default: // name (alphabetical)
                $query->orderBy('name', $this->sortDirection);
                break;
        }

        $bnbList = $query->paginate($this->perPage);

        return view('livewire.bed-and-breakfast.bed-and-breakfast-index', [
            'bnbList' => $bnbList,
            'amenities' => Amenity::orderBy('name')->get(),
        ]);
    }
}
