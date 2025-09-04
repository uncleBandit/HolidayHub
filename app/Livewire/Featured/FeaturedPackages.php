<?php

namespace App\Livewire\Featured;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Package;
use Illuminate\Support\Facades\Cache;

class FeaturedPackages extends Component
{
    use WithPagination;

    // Sets the theme for pagination links to use Tailwind CSS
    protected string $paginationTheme = 'tailwind';

    public $perPage = 6;
    public $search = '';
    public $destination = null;
    public $minPrice = null;
    public $maxPrice = null;
    public $sortBy = 'latest';
    public $page = 1;

    protected $queryString = [
        'search', 'destination', 'minPrice', 'maxPrice', 'sortBy', 'page'
    ];

    /**
     * Resets the page to 1 whenever a filter property is updated.
     */
    public function updating($property)
    {
        if (in_array($property, ['search', 'destination', 'minPrice', 'maxPrice', 'sortBy', 'perPage'])) {
            $this->resetPage();
        }
    }

    /**
     * Renders the component, fetching and caching the packages.
     */
    public function render()
    {
        // Generate a unique cache key based on the current state of the component
        $cacheKey = "featured_packages_" . md5(json_encode([
            'search' => $this->search,
            'destination' => $this->destination,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'sortBy' => $this->sortBy,
            'perPage' => $this->perPage,
            'page' => $this->page,
        ]));

        // Fetch packages from the cache or the database. Cache for 10 minutes.
        $packages = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Package::query()
                ->with('destination')
                ->where('is_featured', true)
                ->when($this->search, fn($query) => $query->where('title', 'like', "%{$this->search}%"))
                ->when($this->destination, fn($query) => $query->where('destination_id', $this->destination))
                ->when($this->minPrice, fn($query) => $query->where('price', '>=', $this->minPrice))
                ->when($this->maxPrice, fn($query) => $query->where('price', '<=', $this->maxPrice))
                ->when($this->sortBy === 'latest', fn($query) => $query->orderBy('updated_at', 'desc'))
                ->when($this->sortBy === 'price_asc', fn($query) => $query->orderBy('price', 'asc'))
                ->when($this->sortBy === 'price_desc', fn($query) => $query->orderBy('price', 'desc'))
                ->paginate($this->perPage);
        });

        return view('livewire.featured.featured-packages', [
            'packages' => $packages,
        ]);
    }
}
