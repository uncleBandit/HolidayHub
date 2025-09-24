<?php

namespace App\Livewire\Search;

use App\Models\Activity;
use App\Models\BedAndBreakfast;
use Livewire\Component;
use App\Models\Hotel;
use App\Models\Package;
use App\Models\Destination;
use App\Models\Villa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;


class SearchBar extends Component
{
    public string $query = '';
    public array $results = [
    'hotels' => [],
    'packages' => [],
    'destinations' => [],
    'villas' => [],
    'bed_and_breakfasts' => [],
    'activities' => [],
];
    public int $highlightIndex = 0;

    public function updatedQuery(): void
    {
        Log::info("Search query: {$this->query}");

        $this->resetResults();

        if (strlen($this->query) > 2) {
            $this->results['destinations'] = Destination::where('name', 'like', "%{$this->query}%")
                ->limit(5)->get()->toArray();

            $this->results['hotels'] = Hotel::where('name', 'like', "%{$this->query}%")
                ->limit(5)->get()->toArray();

            $this->results['packages'] = Package::where('title', 'like', "%{$this->query}%")
                ->limit(5)->get()->toArray();

            $this->results['villas'] = Villa::where('name', 'like', "%{$this->query}%")
            ->limit(5)->get()->toArray();

            $this->results['bnbs'] = BedAndBreakfast::where('name', 'like', "%{$this->query}%")
                ->limit(5)->get()->toArray();

            $this->results['activities'] = Activity::where('name', 'like', "%{$this->query}%")
                ->limit(5)->get()->toArray();
        }

        $this->dispatch('search-results-updated');
    }

    public function resetResults(): void
    {
        $this->results = [
            'destinations' => [],
            'hotels' => [],
            'packages' => [],
            'villas' => [],
            'bnbs' => [],
            'activities' => [],
        ];
    }


    public function mount(): void
    {
        $this->resetResults();
    }

    public function selectResult(string $type, int $id)
    {
        return match ($type) {
            'destination' => redirect()->route('destination.show', Destination::findOrFail($id)),
            'hotel' => redirect()->route('hotel-show',  Hotel::findOrFail($id)),
            'package' => redirect()->route('packages-show', Package::findOrFail($id)),
            'villa' => redirect()->route('villa.show', Villa::findOrFail($id)),
            'bnb' => redirect()->route('bedandbreakfast.show', BedAndBreakfast::findOrFail($id)),
            'activity' => redirect()->route('activity.show', Activity::findOrFail($id)),
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.search.search-bar');
    }
}
