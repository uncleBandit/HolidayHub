<?php

namespace App\Modules\Activities\Presentation\Livewire\Featured;

use App\Modules\Activities\Application\Services\ActivitySearchQuery;
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

    public string $sortBy = 'name';

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
    public function updatingSearch(): void
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
        $sort = $this->sortBy === 'created_at' ? 'latest' : 'rating';
        $activities = app(ActivitySearchQuery::class)->build([
            'search' => $this->search,
            'sort' => $sort,
            'featured' => true,
        ])->paginate(min(50, max(1, $this->perPage)));

        return view('livewire.featured.featured-activities', [
            'activities' => $activities,
        ]);
    }
}
