<?php

namespace App\Livewire\Featured;

use App\Models\Offer;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;

class FeaturedOffers extends Component
{
    use WithPagination;

    // Sets the theme for pagination links to use Tailwind CSS
    protected string $paginationTheme = 'tailwind';

    // Public properties that will be bound to the URL query string
    public string $search = '';
    public int $perPage = 6;
    public string $sortBy = 'rating'; // Options: 'rating' or 'newest'
    public string $direction = 'desc'; // 'asc' or 'desc'
    public $page = 1;

    // Specifies which properties to include in the URL
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'rating'],
        'direction' => ['except' => 'desc'],
        'page' => ['except' => 1]
    ];

    /**
     * Resets the page to 1 whenever the search property is updated.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Renders the component, fetching and caching the offers.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        // Generate a unique cache key based on the current state of the component
        $cacheKey = "featured_offers_{$this->search}_{$this->sortBy}_{$this->direction}_{$this->perPage}_page_" . $this->page;

        // Fetch offers from the cache or the database
        $offers = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Offer::query()
                ->where('is_featured', true)
                ->when($this->search, fn ($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                )
                ->when($this->sortBy === 'rating', fn ($q) => $q->orderBy('rating', $this->direction))
                ->when($this->sortBy === 'newest', fn ($q) => $q->latest())
                ->paginate($this->perPage);
        });

        return view('livewire.featured.featured-offers', [
            'offers' => $offers,
        ]);
    }
}
