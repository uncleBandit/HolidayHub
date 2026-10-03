<?php

namespace App\Modules\Destinations\Presentation\Livewire;

use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Experience;
use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Discover extends Component
{
    public $featuredDestinations = [];

    public $trendingExperiences = [];

    public $recommendedVillas = [];

    public $collections = [];

    public function mount()
    {
        // Use caching for performance (avoid heavy DB hits on every request)
        $this->featuredDestinations = Cache::remember('discover.featured_destinations', 3600, function () {
            return Destination::query()
                ->withCount('bookings')
                ->orderByDesc('bookings_count')
                ->take(6)
                ->get();
        });

        $this->trendingExperiences = Cache::remember('discover.trending_experiences', 3600, function () {
            return Experience::query()
                ->withCount('bookings')
                ->orderByDesc('bookings_count')
                ->take(6)
                ->get();
        });

        $this->recommendedVillas = $this->getPersonalizedVillas();

        // Dynamic collections (curated + data-driven)
        $this->collections = [
            'Romantic Getaways' => Villa::published()
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->take(6)->get(),
            'Adventure Trips' => Experience::where('tags', 'like', '%adventure%')
                ->withCount('bookings')
                ->orderByDesc('bookings_count')
                ->take(6)->get(),
            'Family Escapes' => Villa::published()
                ->where('max_guests', '>=', 6)
                ->withAvg('reviews', 'rating')
                ->take(6)->get(),
        ];
    }

    protected function getPersonalizedVillas()
    {
        if (! Auth::check()) {
            return Villa::query()
                ->published()
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->take(6)
                ->get();
        }

        $user = Auth::user();

        // Personalized logic: visited cities + fallback to favorites
        $visitedCities = $user->bookings()
            ->with('bookable')
            ->get()
            ->pluck('bookable.city')
            ->unique()
            ->filter();
        $wishlistedVillaIds = $user->wishlists()
            ->whereIn('wishlistable_type', [Villa::class, (new Villa)->getMorphClass()])
            ->pluck('wishlistable_id');

        return Villa::query()
            ->published()
            ->when($visitedCities->isNotEmpty() || $wishlistedVillaIds->isNotEmpty(), function ($query) use ($visitedCities, $wishlistedVillaIds) {
                $query->where(function ($query) use ($visitedCities, $wishlistedVillaIds) {
                    if ($visitedCities->isNotEmpty()) {
                        $query->whereIn('city', $visitedCities);
                    }

                    if ($wishlistedVillaIds->isNotEmpty()) {
                        if ($visitedCities->isNotEmpty()) {
                            $query->orWhereIn('villas.id', $wishlistedVillaIds);
                        } else {
                            $query->whereIn('villas.id', $wishlistedVillaIds);
                        }
                    }
                });
            })
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->take(6)
            ->get();
    }

    public function render()
    {
        return view('livewire.discover', [
            'featuredDestinations' => $this->featuredDestinations,
            'trendingExperiences' => $this->trendingExperiences,
            'recommendedVillas' => $this->recommendedVillas,
            'collections' => $this->collections,
        ])->with(['title' => 'Discover Your Next Adventure']);
    }
}
