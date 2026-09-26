<?php

namespace App\Modules\Destinations\Presentation\Livewire\Destination;

use App\Modules\Destinations\Domain\Models\Destination;
use Livewire\Component;

class GalleryAndDetails extends Component
{
    public Destination $destination;

    public function render()
    {
        // Explicitly pass the destination variable to the view
        return view('livewire.destination.gallery-and-details', [
            'destination' => $this->destination,
        ]);
    }
}
