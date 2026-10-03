<?php

namespace App\Modules\Accommodation\Presentation\Livewire\BedAndBreakfast;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Catalog\Domain\Models\Amenity;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function resetFilters()
    {
        $this->reset([
            'search', 'city', 'minPrice', 'maxPrice',
            'selectedAmenities', 'onlyFeatured',
            'sortField', 'sortDirection',
        ]);
    }

    public function render()
    {
        $query = BedAndBreakfast::query()
            ->published()
            ->with(['provider', 'amenities'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereHas('accommodation', function ($query) {
                $query->published()->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                            ->orWhere('city', 'like', "%{$this->search}%")
                            ->orWhere('country', 'like', "%{$this->search}%");
                    });
                })->when($this->city, fn ($q) => $q->where('city', 'like', "%{$this->city}%"))
                    ->when($this->minPrice !== null, fn ($q) => $q->where('avg_price_per_night', '>=', $this->minPrice))
                    ->when($this->maxPrice !== null, fn ($q) => $q->where('avg_price_per_night', '<=', $this->maxPrice))
                    ->when($this->onlyFeatured, fn ($q) => $q->where('is_featured', true));
            });

        // 🔍 Search
        // 🛎️ Amenities
        if (! empty($this->selectedAmenities)) {
            $query->whereHas('amenities', function ($q) {
                $q->whereIn('amenities.id', $this->selectedAmenities);
            }, '=', count($this->selectedAmenities));
        }

        logger($this->selectedAmenities);

        // 📊 Sorting options
        switch ($this->sortField) {
            case 'price':
                $query->orderBy(
                    Accommodation::query()->select('avg_price_per_night')->whereColumn('bookable_id', 'bed_and_breakfasts.id')
                        ->where('bookable_type', (new BedAndBreakfast)->getMorphClass()),
                    in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'asc'
                );
                break;
            case 'rating':
                $query->orderByRaw('reviews_avg_rating IS NULL') // push unrated to bottom
                    ->orderBy('reviews_avg_rating', $this->sortDirection);
                break;

            case 'newest':
                $query->orderBy('created_at', $this->sortDirection);
                break;
            default: // name (alphabetical)
                $query->orderBy(
                    Accommodation::query()->select('name')->whereColumn('bookable_id', 'bed_and_breakfasts.id')
                        ->where('bookable_type', (new BedAndBreakfast)->getMorphClass()),
                    in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'asc'
                );
                break;
        }

        $bnbList = $query->paginate($this->perPage);

        return view('livewire.bed-and-breakfast.bed-and-breakfast-index', [
            'bnbList' => $bnbList,
            'amenities' => Amenity::orderBy('name')->get(),
        ]);
    }
}
