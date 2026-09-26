<?php

namespace App\Modules\Reviews\Presentation\Livewire;

use App\Modules\Reviews\Domain\Models\Review;
use Livewire\Component;

class Testimonials extends Component
{
    // This public property will now hold the collection of reviews.
    // It's available to your view via $testimonials.
    public $testimonials;

    public function mount()
    {
        // Fetch the data and assign it to the public property here.
        // Using `mount()` is a great place to do this as it runs once when the component is initialized.
        $this->testimonials = Review::with('guest.user')
            ->where('status', 'approved')
            ->latest()
            ->take(6)
            ->get();
    }

    public function render()
    {
        // The view can now access the $testimonials property directly.
        // We no longer need to pass it via `compact()`.
        return view('livewire.testimonials');
    }
}
