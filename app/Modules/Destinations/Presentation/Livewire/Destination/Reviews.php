<?php

namespace App\Modules\Destinations\Presentation\Livewire\Destination;

use App\Modules\Destinations\Domain\Models\Destination;
use Livewire\Component;

class Reviews extends Component
{
    public Destination $destination;

    // This method is crucial for handling the typed public property
    public function mount(Destination $destination)
    {
        $this->destination = $destination;
    }

    public function render()
    {
        // The destination property is now guaranteed to be initialized
        $reviews = $this->destination->reviews;

        return view('livewire.destination.reviews', [
            'reviews' => $reviews,
        ]);
    }
}
