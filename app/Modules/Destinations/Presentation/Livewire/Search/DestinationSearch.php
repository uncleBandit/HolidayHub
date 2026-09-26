<?php

namespace App\Modules\Destinations\Presentation\Livewire\Search;

use App\Modules\Destinations\Domain\Models\Destination;
use Livewire\Component;
use Livewire\WithPagination;

class DestinationSearch extends Component
{
    use WithPagination;

    public $searchQuery = '';

    public $sortBy = 'name';

    public $results = [];

    public $showResults = false;

    protected $queryString = ['searchQuery', 'sortBy']; // keeps filters in URL

    public function updatedSearch($value)
    {
        if (strlen($value) > 2) {
            $this->results = Destination::where('name', 'like', '%'.$value.'%')
                ->orWhere('city', 'like', '%'.$value.'%')
                ->orWhere('country', 'like', '%'.$value.'%')
                ->limit(5)
                ->get();
            $this->showResults = true;
        } else {
            $this->results = [];
            $this->showResults = false;
        }

        // reset to first page when search changes
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->results = [];
        $this->showResults = false;
        $this->resetPage();
    }

    public function getDestinationsProperty()
    {
        return Destination::when($this->searchQuery, function ($query) {
            $query->where('name', 'like', '%'.$this->searchQuery.'%')
                ->orWhere('city', 'like', '%'.$this->searchQuery.'%')
                ->orWhere('country', 'like', '%'.$this->searchQuery.'%');
        })
            ->orderBy($this->sortBy, 'asc')
            ->paginate(12);
    }

    public function render()
    {
        return view('livewire.search.destination-search', [
            'destinations' => $this->destinations,
        ]);
    }
}
