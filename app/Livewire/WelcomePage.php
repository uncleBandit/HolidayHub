<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Route;

class WelcomePage extends Component
{
    public $featuredDestinations = [];
    public $offeredHotels = [];
    public $testimonials = [];

    public function mount()
    {
        $this->featuredDestinations = Destination::where('is_featured', true)
            ->orderBy('priority', 'asc')
            ->take(6)
            ->get();

        $this->offeredHotels = Hotel::whereNotNull('avg_price_per_night')
            ->orderByDesc('avg_price_per_night')
            ->take(6)
            ->get();

        $this->testimonials = Testimonial::latest()
            ->take(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'content' => $t->content,
                'name' => $t->guest->user->name ?? 'Anonymous Traveler',
                'avatar' => $t->guest->user->avatar ?? null,
                'location' => $t->guest->location ?? null,
            ]);
    }

    public function render()
    {
        return view('livewire.welcome-page' , [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
    }
}
