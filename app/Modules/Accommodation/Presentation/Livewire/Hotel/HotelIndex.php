<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Hotel;

use App\Modules\Accommodation\Domain\Models\Hotel;
use Livewire\Component;
use Livewire\WithPagination;

class HotelIndex extends Component
{
    use WithPagination;

    public $search = '';

    public $location = '';

    public $minPrice;

    public $maxPrice;

    public $sortField = 'name';

    public $sortDirection = 'asc';

    public $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'location' => ['except' => ''],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updating($field)
    {
        if (in_array($field, ['search', 'location', 'minPrice', 'maxPrice'])) {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $hotels = Hotel::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('city', 'like', '%'.$this->search.'%')
                    ->orWhere('country', 'like', '%'.$this->search.'%');
            })
            ->when($this->location, fn ($q) => $q->where('city', 'like', '%'.$this->location.'%')
                ->orWhere('country', 'like', '%'.$this->location.'%')
            )
            ->when($this->minPrice, fn ($q) => $q->where('price_per_night', '>=', $this->minPrice)
            )
            ->when($this->maxPrice, fn ($q) => $q->where('price_per_night', '<=', $this->maxPrice)
            )
            ->withAvg('reviews', 'rating')
            ->withCount('bookings')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.hotel.hotel-index', [
            'hotels' => $hotels,
        ]);
    }
}
