<?php

namespace App\Modules\Accommodation\Presentation\Livewire;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Destinations\Domain\Models\Destination;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationList extends Component
{
    use WithPagination;

    // UI state
    public string $search = '';

    public ?int $destinationId = null;

    public string $sortBy = 'latest'; // latest, price_low, price_high, rating

    public int $perPage = 12;

    // Advanced filters
    public ?float $minPrice = null;

    public ?float $maxPrice = null;

    public ?float $minRating = null;

    public string $groupBy = 'type'; // type | destination | none

    protected $queryString = [
        'search' => ['except' => ''],
        'destinationId' => ['except' => null],
        'sortBy' => ['except' => 'latest'],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'minRating' => ['except' => null],
        'groupBy' => ['except' => 'type'],
    ];

    public function updating($field)
    {
        if (in_array($field, ['search', 'destinationId', 'sortBy', 'minPrice', 'maxPrice', 'minRating'])) {
            $this->resetPage();
        }
    }

    public function updatedDestinationId($value)
    {
        $this->destinationId = $value === '' ? null : (int) $value;
    }

    private function queryAccommodations()
    {
        return Accommodation::query()
            ->published()
            ->with(['bookable', 'destination'])
            ->withCount('reviews')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%")
                    ->orWhere('country', 'like', "%{$this->search}%");
            }))
            ->byDestination($this->destinationId)
            ->byPriceRange($this->minPrice, $this->maxPrice)
            ->byRating($this->minRating)
            ->when($this->sortBy === 'price_low', fn ($q) => $q->orderBy('avg_price_per_night', 'asc'))
            ->when($this->sortBy === 'price_high', fn ($q) => $q->orderBy('avg_price_per_night', 'desc'))
            ->when($this->sortBy === 'rating', fn ($q) => $q->orderBy('avg_rating', 'desc'))
            ->when($this->sortBy === 'latest', fn ($q) => $q->latest());
    }

    private function groupResults($accommodations)
    {
        $collection = $accommodations->getCollection();

        return match ($this->groupBy) {
            'destination' => $collection->groupBy('destination.name')
                ->map(fn ($items, $destination) => [
                    'group' => $destination,
                    'items' => $items,
                ])
                ->values()
                ->toArray(),

            'type' => $collection->groupBy('bookable_type')
                ->map(fn ($items, $type) => [
                    'group' => class_basename($type),
                    'items' => $items,
                ])
                ->values()
                ->toArray(),

            default => $collection->map(fn ($item) => [
                'group' => 'All',
                'items' => [$item],
            ])->toArray(),
        };
    }

    public function render()
    {
        $accommodations = $this->queryAccommodations()->paginate($this->perPage);

        $groupedAccommodations = $this->groupResults($accommodations);

        return view('livewire.accommodation-list', [
            'accommodations' => $accommodations,
            'groupedAccommodations' => $groupedAccommodations,
            'destinations' => Destination::all(),
        ]);
    }
}
