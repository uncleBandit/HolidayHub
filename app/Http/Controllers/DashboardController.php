<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Activity;
use App\Models\Offer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch featured destinations
        $featuredDestinations = Destination::where('is_featured', true)
            ->take(6)
            ->get();

        // Fetch featured hotels
        $featuredHotels = Hotel::where('is_featured', true)
            ->take(6)
            ->get();

        // Fetch featured activities
        $featuredActivities = Activity::where('is_featured', true)
            ->take(6)
            ->get();

        // Fetch featured offers (e.g., hotels with discounts)
        $featuredOffers = Offer::where('is_featured', true)
            ->take(6)
            ->get();

        // Pass to view
        return view('dashboard', compact(
            'featuredDestinations',
            'featuredHotels',
            'featuredActivities',
            'featuredOffers'
        ));
    }
}
