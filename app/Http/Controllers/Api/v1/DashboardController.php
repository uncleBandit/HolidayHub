<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Activity;
use App\Models\Offer;
use App\Models\Package;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Return featured content for the dashboard.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Limit for featured items
        $limit = 6;

        // Fetch featured destinations
        $featuredDestinations = Destination::where('is_featured', true)
            ->select('id', 'name', 'slug', 'cover_image', 'country')
            ->take($limit)
            ->get();

        // Fetch featured hotels
        $featuredHotels = Hotel::where('is_featured', true)
            ->select('id', 'name', 'slug', 'city', 'country', 'cover_image', 'rating', 'price_range')
            ->take($limit)
            ->get();

        // Fetch featured activities
        $featuredActivities = Activity::where('is_featured', true)
            ->select('id', 'name', 'slug', 'location', 'cover_image', 'price')
            ->take($limit)
            ->get();

        // Fetch featured offers
        $featuredOffers = Offer::where('is_featured', true)
            ->select('id', 'title', 'description', 'discount', 'valid_until', 'image')
            ->take($limit)
            ->get();

        // Fetch featured packages
        $featuredPackages = Package::where('is_featured', true)
            ->select('id', 'title', 'destination', 'price', 'cover_image')
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'featured_destinations' => $featuredDestinations,
                'featured_hotels' => $featuredHotels,
                'featured_activities' => $featuredActivities,
                'featured_offers' => $featuredOffers,
                'featured_packages' => $featuredPackages,
            ]
        ]);
    }
}
