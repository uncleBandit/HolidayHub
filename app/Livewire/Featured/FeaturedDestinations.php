<?php

namespace App\Livewire\Featured;

use App\Models\Destination;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;

class FeaturedDestinations extends Component
{
    use WithPagination;

    // Sets the theme for pagination links to use Tailwind CSS
    protected string $paginationTheme = 'tailwind';

    // Public properties that will be bound to the URL query string
    public string $search = '';
    public int $perPage = 6;
    public string $sortBy = 'name'; // Options: 'name' or 'created_at'
    public string $direction = 'asc'; // 'asc' or 'desc'
    public $page = 1;

    // Specifies which properties to include in the URL
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'direction' => ['except' => 'asc'],
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
     * Renders the component, fetching and caching the destinations.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        // Generate a unique cache key based on the current state of the component
        $cacheKey = "featured_destinations_{$this->search}_{$this->sortBy}_{$this->direction}_{$this->perPage}_page_" . $this->page;

        // Fetch destinations from the cache or the database
        $destinations = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Destination::query()
                ->when(empty($this->search), function ($query) {
                    // No search → only featured destinations
                    $query->where('is_featured', true);
                })
                ->when($this->search, function ($query) {
                    // Search applied → search across all destinations
                    $query->where(function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                          ->orWhere('description', 'like', "%{$this->search}%")
                          ->orWhere('location', 'like', "%{$this->search}%");
                    });
                })
                ->orderByDesc('is_featured')
                ->orderBy($this->sortBy, $this->direction)
                ->paginate($this->perPage);
        });

        return view('livewire.featured.featured-destinations', [
            'destinations' => $destinations,
        ]);
    }
}
