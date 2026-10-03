<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Hotel;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Accommodation;
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
        $sortField = in_array($this->sortField, ['name', 'city', 'created_at', 'avg_rating'], true)
            ? $this->sortField
            : 'name';
        $sortDirection = in_array($this->sortDirection, ['asc', 'desc'], true)
            ? $this->sortDirection
            : 'asc';

        $hotels = Hotel::query()
            ->published()
            ->whereHas('accommodation', function ($query) {
                $query->published()->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('city', 'like', '%'.$this->search.'%')
                            ->orWhere('country', 'like', '%'.$this->search.'%');
                    });
                });
            })
            ->whereHas('accommodation', fn ($query) => $query->published()
                ->when($this->location, fn ($q) => $q->where(function ($q) {
                    $q->where('city', 'like', '%'.$this->location.'%')
                        ->orWhere('country', 'like', '%'.$this->location.'%');
                }))
                ->when($this->minPrice !== null, fn ($q) => $q->where('avg_price_per_night', '>=', $this->minPrice))
                ->when($this->maxPrice !== null, fn ($q) => $q->where('avg_price_per_night', '<=', $this->maxPrice))
            )
            ->withAvg('reviews', 'rating')
            ->withCount('bookings')
            ->with('accommodation')
            ->when($sortField === 'avg_rating', fn ($q) => $q->orderBy(
                Accommodation::query()->select('avg_rating')->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass()),
                $sortDirection
            ))
            ->when($sortField !== 'avg_rating', fn ($q) => $q->orderBy(
                Accommodation::query()->select($sortField)->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass()),
                $sortDirection
            ))
            ->paginate($this->perPage);

        return view('livewire.hotel.hotel-index', [
            'hotels' => $hotels,
        ]);
    }
}
