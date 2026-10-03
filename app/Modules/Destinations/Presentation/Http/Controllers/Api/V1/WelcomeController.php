<?php

namespace App\Modules\Destinations\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Reviews\Domain\Models\Review;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    /**
     * Display the main landing page with featured content.
     */
    public function index()
    {
        // 1. Featured Destinations
        $featuredDestinations = Destination::query()
            ->where('is_featured', true)
            ->orderBy('priority', 'asc') // custom priority or ranking system
            ->take(6)
            ->get();

        // 2. Offered Hotels
        $offeredHotels = Hotel::query()
            ->published()
            ->whereHas('accommodation', fn ($query) => $query->whereNotNull('avg_price_per_night'))
            ->orderByDesc('is_featured')
            ->take(6)
            ->get();

        // 3. Recent Testimonials
        $testimonials = Review::query()
            ->with('guest.user:id,name,avatar') // eager load user details
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Welcome', [
            'featuredDestinations' => $featuredDestinations,
            'offeredHotels' => $offeredHotels,
            'testimonials' => $testimonials,
        ]);
    }
}
