<?php

namespace App\Modules\Destinations\Presentation\Livewire;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

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

        $this->offeredHotels = Hotel::published()
            ->whereHas('accommodation', fn ($query) => $query->whereNotNull('avg_price_per_night'))
            ->with('accommodation')
            ->orderByDesc(
                \App\Modules\Accommodation\Domain\Models\Accommodation::query()
                    ->select('avg_price_per_night')
                    ->whereColumn('bookable_id', 'hotels.id')
                    ->where('bookable_type', (new Hotel)->getMorphClass())
            )
            ->take(6)
            ->get();

        $this->testimonials = Review::latest()
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
        return view('livewire.welcome-page', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }
}
