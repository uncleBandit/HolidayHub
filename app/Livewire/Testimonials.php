<?php

namespace App\Livewire;

use App\Models\Review;
use App\Models\Testimonial;
use Livewire\Component;

class Testimonials extends Component
{
    public $testimonials;

    public function mount()
    {
        $this->testimonials = Review::latest()->take(6)->get();
    }
    public function render()
   {
    $testimonials = Review::with('guest.user')
        ->latest()
        ->take(6)
        ->get();

    return view('livewire.testimonials', compact('testimonials'));
   }

}
