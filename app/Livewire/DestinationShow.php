<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Destination;
use Livewire\WithPagination;

class DestinationShow extends Component
{
    use WithPagination;

    public Destination $destination;
    public string $activeTab = 'overview';
    public int $perPage = 6;

    public function mount(Destination $destination)
    {
    $this->destination = $destination->load([
        'accommodations.bookable,destination_id,name,price_range,cover_image,availability',
        'activities:id,destination_id,title,price,duration,cover_image',
        'reviews:id,reviewable_id,reviewable_type,user_id,rating,comment',
        'reviews.user:id,name,avatar'
    ]);
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.destination-show', [
            'accommodations' => $this->destination->accommodations()->paginate($this->perPage),
            'activities' => $this->destination->activities()->paginate($this->perPage),
        ]);
    }
}
