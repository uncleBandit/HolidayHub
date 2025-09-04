<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAgentRequest;
use App\Http\Requests\UpdateAgentRequest;

class AgentController extends Controller
{
    /**
     * Get agent dashboard data.
     */
    public function dashboard()
    {
        $agent = Auth::user()->agent;

        $packages = $agent->packages()->with('destination')->latest()->paginate(10);
        $bookingsCount = $agent->packages()->withCount('bookings')->get()->sum('bookings_count');

        return response()->json([
            'agent' => $agent,
            'packages' => $packages,
            'bookings_count' => $bookingsCount,
        ]);
    }

    /**
     * List agent packages (paginated).
     */
    public function packages()
    {
        $agent = Auth::user()->agent;
        $packages = $agent->packages()->with('destination')->latest()->paginate(10);

        return response()->json($packages);
    }

    /**
     * Store a new package.
     */
    public function storePackage(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'destination_id' => 'required|exists:destinations,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:available_from',
        ]);

        $package = Auth::user()->agent->packages()->create($request->all());

        return response()->json([
            'success' => true,
            'package' => $package,
            'message' => 'Package created successfully.',
        ], 201);
    }

    /**
     * Show a specific package.
     */
    public function showPackage(Package $package)
    {
        $this->authorize('view', $package);

        return response()->json($package->load('destination', 'bookings.user'));
    }

    /**
     * Update a package.
     */
    public function updatePackage(Request $request, Package $package)
    {
        $this->authorize('update', $package);

        $request->validate([
            'title' => 'required|string|max:255',
            'destination_id' => 'required|exists:destinations,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after_or_equal:available_from',
        ]);

        $package->update($request->all());

        return response()->json([
            'success' => true,
            'package' => $package,
            'message' => 'Package updated successfully.',
        ]);
    }

    /**
     * Delete a package.
     */
    public function destroyPackage(Package $package)
    {
        $this->authorize('delete', $package);

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully.',
        ]);
    }

    /**
     * List bookings for agent's packages.
     */
    public function bookings()
    {
        $agent = Auth::user()->agent;
        $bookings = $agent->packages()->with('bookings.user')->get()->pluck('bookings')->flatten();

        return response()->json($bookings);
    }

    /**
     * Get agent profile.
     */
    public function profile()
    {
        $agent = Auth::user()->agent;

        return response()->json($agent);
    }

    /**
     * Update agent profile.
     */
    public function updateProfile(UpdateAgentRequest $request)
    {
        $agent = Auth::user()->agent;
        $agent->update($request->validated());

        return response()->json([
            'success' => true,
            'agent' => $agent,
            'message' => 'Profile updated successfully.',
        ]);
    }

    /**
     * Analytics and statistics.
     */
    public function analytics()
    {
        $agent = Auth::user()->agent;

        $packagesCount = $agent->packages()->count();
        $bookingsCount = $agent->packages()->withCount('bookings')->get()->sum('bookings_count');
        $totalRevenue = $agent->packages()->withSum('bookings', 'total_price')->get()->sum('bookings_sum_total_price');

        return response()->json([
            'packages_count' => $packagesCount,
            'bookings_count' => $bookingsCount,
            'total_revenue' => $totalRevenue,
        ]);
    }
}
