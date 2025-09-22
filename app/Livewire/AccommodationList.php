<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Accommodation;

class AccommodationList extends Component
{
    use WithPagination;

    // UI state
    public string $search = '';
    public ?int $destinationId = null;
    public string $sortBy = 'latest'; // latest, price_low, price_high, rating
    public int $perPage = 12;

    public array $groupedAccommodations = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'destinationId' => ['except' => null],
        'sortBy' => ['except' => 'latest'],
    ];

    public function updating($field)
    {
        if (in_array($field, ['search', 'destinationId', 'sortBy'])) {
            $this->resetPage();
        }
    }

    public function loadAccommodations()
    {
        $query = Accommodation::query()
            ->with(['bookable', 'destination'])
            ->withCount('reviews')
            ->when($this->search, fn($q) =>
                $q->whereHas('bookable', fn($sub) =>
                    $sub->where('name', 'like', "%{$this->search}%")
                )
            )
            ->when($this->destinationId, fn($q) =>
                $q->where('destination_id', $this->destinationId)
            );

        // Sorting logic
        $query->when($this->sortBy === 'price_low', fn($q) => $q->orderBy('avg_price_per_night', 'asc'))
              ->when($this->sortBy === 'price_high', fn($q) => $q->orderBy('avg_price_per_night', 'desc'))
              ->when($this->sortBy === 'rating', fn($q) => $q->orderBy('avg_rating', 'desc'))
              ->when($this->sortBy === 'latest', fn($q) => $q->latest());

        $accommodations = $query->paginate($this->perPage);

        // Group by bookable type
        $this->groupedAccommodations = $accommodations
            ->groupBy('bookable_type')
            ->map(fn($items, $type) => [
                'type' => class_basename($type),
                'items' => $items,
            ])
            ->values()
            ->toArray();

        return $accommodations;
    }

    public function render()
    {
        $accommodations = $this->loadAccommodations();

        return view('livewire.accommodation-list', [
            'accommodations' => $accommodations,
        ]);
    }
}
