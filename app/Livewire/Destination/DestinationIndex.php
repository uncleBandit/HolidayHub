<?php

namespace App\Livewire\Destination;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Destination;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;

class DestinationIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(except: 'name')]
    public string $sortField = 'name'; // default alphabetical

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc'; // asc/desc toggle

    public int $perPage = 12;

    #[On('refreshDestinations')]
    public function refreshDestinations(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    protected function queryDestinations()
    {
        return Destination::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('country', 'like', "%{$this->search}%")
                      ->orWhere('city', 'like', "%{$this->search}%");
                });
            })
            ->withAvg('reviews', 'rating')
            ->withCount('bookings')
            ->when($this->sortField === 'trending', function ($query) {
                $query->orderByRaw('(bookings_count * 2 + COALESCE(reviews_avg_rating, 0)) desc');
            }, function ($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            });
    }

    public function render()
    {
        $cacheKey = sprintf(
            'destinations.index.%s.%s.%s.%s',
            $this->search,
            $this->sortField,
            $this->sortDirection,
            $this->getPage() // ✅ fixed for LW3
        );

        $destinations = Cache::remember($cacheKey, 60, function () {
            return $this->queryDestinations()->paginate($this->perPage);
        });

        return view('livewire.destination.destination-index', [
            'destinations' => $destinations,
        ]);
    }
}
