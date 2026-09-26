<?php

namespace App\Modules\Destinations\Presentation\Livewire\Featured;

use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class FeaturedDestinations extends Component
{
    use WithPagination;

    // Sets the theme for pagination links to use Tailwind CSS
    protected string $paginationTheme = 'tailwind';

    // Public properties that will be bound to the URL query string
    public string $search = '';

    public int $perPage = 6;

    public string $sortBy = 'name'; // Options: 'name', 'rating', 'created_at'

    public string $direction = 'asc'; // 'asc' or 'desc'

    public $page = 1;

    // New property to control the visibility of the search dropdown
    public bool $showResults = false;

    // Specifies which properties to include in the URL
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'direction' => ['except' => 'asc'],
        'page' => ['except' => 1],
    ];

    /**
     * Resets the page to 1 whenever the search property is updated.
     */
    public function updatingSearch()
    {
        $this->resetPage();
        // Show the results dropdown when the user starts typing
        if (! empty($this->search)) {
            $this->showResults = true;
        }
    }

    /**
     * Renders the component, fetching and caching the destinations.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        // Generate a unique cache key based on the current state of the component
        $cacheKey = "featured_destinations_{$this->search}_{$this->sortBy}_{$this->direction}_{$this->perPage}_page_".$this->page;

        // Fetch destinations from the cache or the database
        $destinations = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $query = Destination::query();

            if (empty($this->search)) {
                // No search term: only show featured destinations
                $query->where('is_featured', true);
            } else {
                // Search term exists: search across all destinations
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('location', 'like', "%{$this->search}%");
                });
            }

            // Always prioritize featured destinations in the sort order
            $query->orderByDesc('is_featured')
                ->orderBy($this->sortBy, $this->direction);

            return $query->paginate($this->perPage);
        });

        // Set showResults to false if the search is empty to hide the dropdown
        if (empty($this->search)) {
            $this->showResults = false;
        }

        return view('livewire.featured.featured-destinations', [
            'destinations' => $destinations,
        ]);
    }
}
