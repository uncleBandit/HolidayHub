<?php

namespace App\Modules\Destinations\Presentation\Livewire\Destination;

use App\Modules\Destinations\Domain\Models\Destination;
use Livewire\Component;
use Livewire\WithPagination;

class DestinationShow extends Component
{
    use WithPagination;

    public Destination $destination;

    public string $activeTab = 'overview';

    public int $perPage = 6;

    public function mount(Destination $destination)
    {
        // Eager-load all relationships the view will need.
        $this->destination = $destination->load([
            'accommodations.bookable.reviews',
            'hotels',
            'bedAndBreakfasts',
            'activities',
            'reviews.guest',
        ])->loadCount([
            'accommodations as hotels_count',
            'accommodations as bnb_count',
            'activities',
            'reviews',
        ])->loadAvg('reviews', 'rating');

        // Filter accommodations for hotels and bnb
        $this->destination->hotels = $this->destination->accommodations->where('bookable_type', \App\Modules\Accommodation\Domain\Models\Hotel::class);
        $this->destination->bedAndBreakfasts = $this->destination->accommodations->where('bookable_type', \App\Modules\Accommodation\Domain\Models\BedAndBreakfast::class);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    // app/Livewire/Destination/DestinationShow.php

    public function render()
    {
        // Get the eager-loaded accommodations collection
        $accommodations = $this->destination->accommodations;

        // Manually paginate the collection
        $currentPage = $this->getPage();
        $pagedData = $accommodations->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();

        $paginatedAccommodations = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $accommodations->count(),
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        // Pass the paginated collections and the destination model to the view.
        return view('livewire.destination.destination-show', [
            'accommodations' => $paginatedAccommodations,
            'activities' => $this->destination->activities()->paginate($this->perPage),
        ]);
    }
}
