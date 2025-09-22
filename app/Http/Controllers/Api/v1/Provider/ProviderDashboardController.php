<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccommodationResource;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\ProviderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Provider;
use App\Models\ProviderProfile;

class ProviderDashboardController extends Controller
{
    /**
     * Provider Dashboard Overview
     *
     * Returns aggregated metrics, profile, services, and upcoming bookings
     */
    public function index()
    {
        $providerId = Auth::id();

        // Metrics
        $pendingBookings = Booking::whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
            $query->where('provider_id', $providerId);
        })->where('status', 'pending')->count();

        $confirmedBookings = Booking::whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
            $query->where('provider_id', $providerId);
        })->where('status', 'confirmed')->count();

        $totalRevenue = Booking::whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
            $query->where('provider_id', $providerId);
        })->where('status', 'confirmed')->sum('total_price');

        // Profile
        $profile = Provider::where('provider_id', $providerId)->first();

        // Services
        $accommodations = Accommodation::where('provider_id', $providerId)->get();
        $activities = Activity::where('provider_id', $providerId)->get();

        // Upcoming bookings
        $upcomingBookings = Booking::with(['bookable', 'user'])
            ->whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->where('status', 'confirmed')
            ->orderBy('start_date')
            ->take(5)
            ->get();

        return response()->json([
            'metrics' => [
                'pending_bookings'   => $pendingBookings,
                'confirmed_bookings' => $confirmedBookings,
                'total_revenue'      => $totalRevenue,
            ],
            'profile' => new ProviderResource($profile),
            'services' => [
                'accommodations' => AccommodationResource::collection($accommodations),
                'activities'     => ActivityResource::collection($activities),
            ],
            'upcoming_bookings' => BookingResource::collection($upcomingBookings),
        ]);
    }

    /**
     * Update booking status (API-first)
     */
    public function updateBookingStatus(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->bookable->provider_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled,pending',
        ]);

        $booking->update($validated);

        return new BookingResource($booking);
    }
}
