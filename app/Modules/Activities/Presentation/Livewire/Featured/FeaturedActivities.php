<?php

namespace App\Modules\Activities\Presentation\Livewire\Featured;

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class FeaturedActivities extends Component
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
        'page' => ['except' => 1],
    ];

    /**
     * Resets the page to 1 whenever the search property is updated.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Renders the component, fetching and caching the activities.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        // Generate a unique cache key based on the current state of the component
        $cacheKey = "featured_activities_{$this->search}_{$this->sortBy}_{$this->direction}_{$this->perPage}_page_".$this->page;

        // Fetch activities from the cache or the database
        $activities = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Activity::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                )
                ->orderBy($this->sortBy, $this->direction)
                ->paginate($this->perPage);
        });

        return view('livewire.featured.featured-activities', [
            'activities' => $activities,
        ]);
    }
}
