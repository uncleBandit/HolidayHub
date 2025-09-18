<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Destination;
use App\Models\Experience;
use App\Models\Villa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            'Romantic Getaways' => Villa::where('tags', 'like', '%romantic%')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->take(6)->get(),
            'Adventure Trips' => Experience::where('tags', 'like', '%adventure%')
                ->withCount('bookings')
                ->orderByDesc('bookings_count')
                ->take(6)->get(),
            'Family Escapes' => Villa::where('tags', 'like', '%family%')
                ->orWhere('max_guests', '>=', 6)
                ->withAvg('reviews', 'rating')
                ->take(6)->get(),
        ];
    }

    protected function getPersonalizedVillas()
    {
        if (!Auth::check()) {
            return Villa::query()
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

        return Villa::query()
            ->when($visitedCities->isNotEmpty(), function ($query) use ($visitedCities) {
                return $query->whereIn('city', $visitedCities);
            })
            ->orWhereIn('id', $user->wishlist()->pluck('villa_id')) // favorited villas
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
