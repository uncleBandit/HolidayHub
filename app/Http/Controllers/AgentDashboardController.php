<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Package;
use App\Models\Destination;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Booking;

class AgentDashboardController extends Controller
{
    /**
     * Show the agent dashboard with key metrics.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // This is where you would fetch data for your dashboard widgets
        // and pass it to the view.
        $totalPackages = Package::count();
        $totalBookings = Booking::count();
        $upcomingTrips = Booking::where('departure_date', '>', now())->count();
        // Assuming 'reviews' is another model, you would fetch pending reviews here
        $pendingReviews = 0; // Placeholder until a review model is set up

        return view('agent.dashboard.index', [
            'totalPackages' => $totalPackages,
            'totalBookings' => $totalBookings,
            'upcomingTrips' => $upcomingTrips,
            'pendingReviews' => $pendingReviews,
        ]);
    }

    /**
     * Show the form for creating a new package.
     *
     * @return \Illuminate\View\View
     */
    public function createPackage()
    {
        // Fetch data required for the package creation form
        $destinations = Destination::all();
        $accommodations = Accommodation::all();
        $activities = Activity::all();

        return view('agent.packages.create', compact('destinations', 'accommodations', 'activities'));
    }

    /**
     * Store a newly created package in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePackage(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'destination_id' => 'required|exists:destinations,id',
            'accommodation_ids' => 'array',
            'activity_ids' => 'array',
        ]);

        // Create the new package
        $package = new Package();
        $package->name = $request->name;
        $package->description = $request->description;
        $package->destination_id = $request->destination_id;
        $package->agent_id = Auth::id(); // Assign the authenticated agent's ID
        $package->save();

        // Attach accommodations and activities
        if ($request->has('accommodation_ids')) {
            $package->accommodations()->sync($request->accommodation_ids);
        }
        if ($request->has('activity_ids')) {
            $package->activities()->sync($request->activity_ids);
        }

        return redirect()->route('agent.dashboard')->with('success', 'Package created successfully!');
    }

    /**
     * Display a list of all packages.
     *
     * @return \Illuminate\View\View
     */
    public function listPackages()
    {
        $packages = Package::with(['destination', 'accommodations', 'activities'])->get();

        return view('agent.packages.list', compact('packages'));
    }

    /**
     * Display a list of all bookings.
     *
     * @return \Illuminate\View\View
     */
    public function listBookings()
    {
        $bookings = Booking::with('package')->get();

        return view('agent.bookings.list', compact('bookings'));
    }
}
