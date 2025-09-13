<?php

namespace App\Http\Controllers\Api\v1\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Package;
use App\Models\Destination;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class AgentDashboardController extends Controller
{
    /**
     * Get key metrics for the agent dashboard.
     */
    public function index(): JsonResponse
    {
        $agentId = Auth::id();

        return response()->json([
            'data' => [
                'total_packages'   => Package::where('agent_id', $agentId)->count(),
                'total_bookings'   => Booking::whereHas('package', fn ($q) => $q->where('agent_id', $agentId))->count(),
                'upcoming_trips'   => Booking::whereHas('package', fn ($q) => $q->where('agent_id', $agentId))
                                              ->where('departure_date', '>', now())->count(),
                'pending_reviews'  => 0, // TODO: integrate with Review model later
            ],
            'message' => 'Agent dashboard metrics fetched successfully.',
        ]);
    }

    /**
     * Store a newly created package.
     */
    public function storePackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'required|string',
            'destination_id' => 'required|exists:destinations,id',
            'accommodation_ids' => 'array',
            'activity_ids'      => 'array',
        ]);

        $package = Package::create([
            'name'          => $validated['name'],
            'description'   => $validated['description'],
            'destination_id'=> $validated['destination_id'],
            'agent_id'      => Auth::id(),
        ]);

        if (!empty($validated['accommodation_ids'])) {
            $package->accommodations()->sync($validated['accommodation_ids']);
        }
        if (!empty($validated['activity_ids'])) {
            $package->activities()->sync($validated['activity_ids']);
        }

        return response()->json([
            'data'    => $package->load(['destination', 'accommodations', 'activities']),
            'message' => 'Package created successfully.',
        ], 201);
    }

    /**
     * List all packages belonging to the agent.
     */
    public function listPackages(): JsonResponse
    {
        $packages = Package::with(['destination', 'accommodations', 'activities'])
            ->where('agent_id', Auth::id())
            ->get();

        return response()->json([
            'data' => $packages,
            'message' => 'Packages fetched successfully.',
        ]);
    }

    /**
     * List all bookings belonging to the agent’s packages.
     */
    public function listBookings(): JsonResponse
    {
        $bookings = Booking::with(['package.destination'])
            ->whereHas('package', fn ($q) => $q->where('agent_id', Auth::id()))
            ->get();

        return response()->json([
            'data' => $bookings,
            'message' => 'Bookings fetched successfully.',
        ]);
    }
}
