<?php

namespace App\Livewire\Search;

use Livewire\Component;
use App\Models\Destination; // Make sure this model exists

class HolidaySearch extends Component
{
    public $searchQuery = '';
    public $results = [];
    public $showResults = false;

    // This method is called automatically by Livewire when `searchQuery` is updated.
    // The `.live.debounce.300ms` modifier in the Blade view controls when it fires.
    public function updatedSearchQuery($value)
    {
        if (strlen($value) > 2) {
            // Perform a search query against your Destination model.
            // You can also search other models like Hotels, Activities, etc.
            $this->results = Destination::where('city', 'like', '%' . $value . '%')
                ->orWhere('country', 'like', '%' . $value . '%')
                ->orWhere('name', 'like', '%' . $value . '%')
                ->limit(5)
                ->get();
            $this->showResults = true;
        } else {
            $this->results = [];
            $this->showResults = false;
        }
    }

    // A method to clear the search results and hide the dropdown.
    public function clearSearch()
    {
        $this->results = [];
        $this->showResults = false;
        $this->searchQuery = '';
    }

    public function render()
    {
        return view('livewire.search.holiday-search');
    }
}
