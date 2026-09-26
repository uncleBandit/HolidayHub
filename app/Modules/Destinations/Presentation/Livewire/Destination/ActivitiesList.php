<?php

namespace App\Modules\Destinations\Presentation\Livewire\Destination;

use App\Modules\Activities\Domain\Models\Activity;
use Livewire\Component;

class ActivitiesList extends Component
{
    // Make sure this matches the parameter name in the parent view
    public $destinationId;

    public function render()
    {
        // Fetch the activities based on the destination ID
        $activities = Activity::where('destination_id', $this->destinationId)->get();

        // Pass the fetched activities to the view
        return view('livewire.destination.activities-list', [
            'activities' => $activities,

        ]);
    }
}
