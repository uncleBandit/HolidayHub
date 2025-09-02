<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Testimonial;
use Illuminate\Http\Request;
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
            ->whereNotNull('discount_percentage')
            ->where('discount_percentage', '>', 0)
            ->orderByDesc('discount_percentage')
            ->take(6)
            ->get();

        // 3. Recent Testimonials
        $testimonials = Testimonial::query()
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
