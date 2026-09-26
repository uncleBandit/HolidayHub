<?php

namespace App\Modules\Destinations\Presentation\Livewire\Destination;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use Livewire\Component;
use Livewire\WithPagination;

class AccommodationsList extends Component
{
    use WithPagination;

    public $destinationId;

    public $perPage = 6;

    public string $sortBy = 'price_asc';

    public array $filters = [];

    public function updating()
    {
        $this->resetPage(); // Reset pagination when filters change
    }

    public function render()
    {
        $query = Accommodation::query()
            ->where('destination_id', $this->destinationId);
        // Add more filtering logic here based on $this->filters

        // Order results
        if ($this->sortBy === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($this->sortBy === 'rating_desc') {
            $query->orderBy('rating', 'desc');
        }

        return view('livewire.destination.accommodations-list', [
            'accommodations' => $query->paginate($this->perPage),
        ]);
    }
}
