<?php

namespace App\Modules\Destinations\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Destinations\Presentation\Http\Requests\StoreDestinationRequest;
use App\Modules\Destinations\Presentation\Http\Requests\UpdateDestinationRequest;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    /**
     * Display a listing of destinations with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = Destination::query()
            ->withCount(['hotels', 'activities', 'reviews'])
            ->withAvg('reviews', 'rating');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%")
                    ->orWhere('country', 'like', "%$search%");
            });
        }

        // Sorting (default: rating desc)
        $sort = $request->input('sort', 'rating_desc');
        match ($sort) {
            'rating_asc' => $query->orderBy('reviews_avg_rating', 'asc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'newest' => $query->latest(),
            default => $query->orderBy('reviews_avg_rating', 'desc'),
        };

        $destinations = $query->paginate($request->input('per_page', 12));

        return response()->json($destinations);
    }

    /**
     * Store a newly created destination (admin only).
     */
    public function store(StoreDestinationRequest $request)
    {
        $destination = Destination::create($request->validated());

        return response()->json([
            'message' => 'Destination created successfully.',
            'data' => $destination,
        ], 201);
    }

    /**
     * Display a specific destination with rich relationships.
     */
    public function show(Destination $destination)
    {
        $destination->load([
            'hotels:id,destination_id,name,price_range,cover_image',
            'activities:id,destination_id,title,price,duration,cover_image',
            'reviews.user:id,name,avatar', // nested relationship for social proof
        ]);

        return response()->json($destination);
    }

    /**
     * Update an existing destination (admin only).
     */
    public function update(UpdateDestinationRequest $request, Destination $destination)
    {
        $destination->update($request->validated());

        return response()->json([
            'message' => 'Destination updated successfully.',
            'data' => $destination,
        ]);
    }

    /**
     * Remove a destination (soft delete).
     */
    public function destroy(Destination $destination)
    {
        $destination->delete();

        return response()->json([
            'message' => 'Destination deleted successfully.',
        ]);
    }
}
