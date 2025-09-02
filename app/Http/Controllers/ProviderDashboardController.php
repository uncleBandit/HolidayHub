<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
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
     * Show the main provider dashboard.
     * This method fetches key metrics for the provider.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get the authenticated provider's ID
        $providerId = Auth::id();

        // Count pending bookings for the provider's services (accommodations and activities)
        $pendingBookings = Booking::whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
            $query->where('provider_id', $providerId);
        })->where('status', 'pending')->count();

        // Fetch the provider's profile for display
        $profile = Provider::where('provider_id', $providerId)->first();

        // Fetch a list of upcoming bookings for display on the dashboard
        $upcomingBookings = Booking::with('bookable')
            ->whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->where('status', 'confirmed')
            ->orderBy('start_date')
            ->take(5)
            ->get();

        return view('provider.dashboard.index', [
            'pendingBookings' => $pendingBookings,
            'profile' => $profile,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }

    /**
     * Show the provider's profile editing form.
     *
     * @return \Illuminate\View\View
     */
    public function showProfileForm()
    {
        $providerId = Auth::id();
        $profile = Provider::firstOrCreate(['provider_id' => $providerId]);

        return view('provider.profile.edit', compact('profile'));
    }

    /**
     * Update the provider's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $providerId = Auth::id();
        $profile = Provider::firstOrCreate(['provider_id' => $providerId]);

        $request->validate([
            'company_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        $profile->update($request->only(['company_name', 'description', 'contact_email', 'contact_phone']));

        return redirect()->route('provider.dashboard')->with('success', 'Profile updated successfully!');
    }

    /**
     * Show a list of all services (accommodations and activities) managed by the provider.
     *
     * @return \Illuminate\View\View
     */
    public function showServices()
    {
        $providerId = Auth::id();
        $accommodations = Accommodation::where('provider_id', $providerId)->get();
        $activities = Activity::where('provider_id', $providerId)->get();

        return view('provider.services.index', compact('accommodations', 'activities'));
    }

    /**
     * Display a list of all bookings for the provider's services.
     *
     * @return \Illuminate\View\View
     */
    public function showBookings()
    {
        $providerId = Auth::id();
        $bookings = Booking::with('user', 'bookable')
            ->whereHasMorph('bookable', [Accommodation::class, Activity::class], function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })->latest()->get();

        return view('provider.bookings.index', compact('bookings'));
    }

    /**
     * Update the status of a specific booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $bookingId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBookingStatus(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        // Ensure the booking belongs to the authenticated provider
        if ($booking->bookable->provider_id !== Auth::id()) {
            abort(403);
        }

        $request->validate(['status' => 'required|in:confirmed,cancelled,pending']);
        $booking->status = $request->status;
        $booking->save();

        return redirect()->back()->with('success', 'Booking status updated.');
    }
}
