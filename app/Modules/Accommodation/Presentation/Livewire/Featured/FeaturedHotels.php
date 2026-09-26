<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Featured;

use App\Modules\Accommodation\Domain\Models\Hotel;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class FeaturedHotels extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public int $perPage = 6;

    public string $sortBy = 'rating';

    public string $direction = 'desc';

    public $page = 1;

    protected $queryString = ['search', 'sortBy', 'direction', 'page'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Update the sort column and toggle the direction.
     */
    public function setSortBy(string $sortColumn)
    {
        // If the user clicks on the same sort option, reverse the direction.
        if ($this->sortBy === $sortColumn) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            // Otherwise, set the new sort column and reset to descending.
            $this->sortBy = $sortColumn;
            $this->direction = 'desc';
        }

        // Reset the page to show the new sort results from the beginning.
        $this->resetPage();
    }

    public function render()
    {
        $cacheKey = "featured_hotels_{$this->search}_{$this->sortBy}_{$this->direction}_{$this->perPage}_page_".$this->page;

        $hotels = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Hotel::query()
                ->when(empty($this->search), function ($query) {
                    // No search → show only featured hotels
                    $query->where('is_featured', true);
                })
                ->when($this->search, function ($query) {
                    // Search applied → search across all hotels
                    $query->where(function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                            ->orWhere('city', 'like', "%{$this->search}%");
                    });
                })
                ->orderByDesc('is_featured')
                ->orderBy($this->sortBy, $this->direction)
                ->paginate($this->perPage);
        });

        return view('livewire.featured.featured-hotels', [
            'hotels' => $hotels,
        ]);
    }
}
