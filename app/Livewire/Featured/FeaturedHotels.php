<?php

namespace App\Livewire\Featured;

use App\Models\Hotel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;

class FeaturedHotels extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public int $perPage = 6;
    public string $sortBy = 'rating'; // rating | stars | newest
    public string $direction = 'desc';
    public $page = 1;


    protected $queryString = ['search', 'sortBy', 'direction', 'page'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $cacheKey = "featured_hotels_{$this->search}_{$this->sortBy}_{$this->direction}_{$this->perPage}_page_" . $this->page;

        $hotels = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Hotel::query()
                ->where('is_featured', true)
                ->when($this->search, fn ($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('city', 'like', "%{$this->search}%")
                )
                ->orderBy($this->sortBy, $this->direction)
                ->paginate($this->perPage);
        });

        return view('livewire.featured.featured-hotels', [
            'hotels' => $hotels,
        ]);
    }
}
