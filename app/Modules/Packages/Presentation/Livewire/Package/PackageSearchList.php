<?php

namespace App\Modules\Packages\Presentation\Livewire\Package;

use App\Modules\Packages\Domain\Models\Package;
use Livewire\Component;
use Livewire\WithPagination;

class PackageSearchList extends Component
{
    use WithPagination;

    // Public properties for filters, synced with the URL
    public string $search = '';

    public string $sortBy = 'newest';

    public ?string $country = null;

    public ?float $minPrice = null;

    public ?float $maxPrice = null;

    // The destination ID is passed into the component
    public $destinationId;

    // Livewire will automatically sync these with the URL query string
    protected $queryString = ['search', 'sortBy', 'country', 'minPrice', 'maxPrice'];

    // This method is called when a query string property is updated.
    // It resets the pagination to the first page.
    public function updating($name)
    {
        if (in_array($name, ['search', 'country', 'sortBy', 'minPrice', 'maxPrice'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Package::with(['destination'])
            // Always filter by destination first
            ->where('destination_id', $this->destinationId)
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('destination', 'like', "%{$this->search}%")
            )
            )
            ->when($this->country, fn ($q) => $q->where('country', $this->country))
            ->when($this->minPrice, fn ($q) => $q->where('final_price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn ($q) => $q->where('final_price', '<=', $this->maxPrice));

        // Apply sorting based on the sortBy property
        if ($this->sortBy === 'price_low') {
            $query->orderBy('final_price', 'asc');
        } elseif ($this->sortBy === 'price_high') {
            $query->orderBy('final_price', 'desc');
        } else {
            // Default sort by 'newest'
            $query->latest();
        }

        return view('livewire.package.package-search-list', [
            'packages' => $query->paginate(9),
        ]);
    }
}
