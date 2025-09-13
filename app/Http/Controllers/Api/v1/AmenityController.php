<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAmenityRequest;
use App\Http\Requests\UpdateAmenityRequest;
use Illuminate\Http\JsonResponse;

class AmenityController extends Controller
{
    /**
     * Display a paginated listing of amenities, optionally searchable.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Amenity::query();

        // Optional search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $amenities = $query->orderBy('name')
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $amenities,
        ]);
    }

    /**
     * Store a newly created amenity.
     */
    public function store(StoreAmenityRequest $request): JsonResponse
    {
        $amenity = Amenity::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $amenity,
            'message' => 'Amenity created successfully.',
        ], 201);
    }

    /**
     * Display a single amenity.
     */
    public function show(Amenity $amenity): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $amenity,
        ]);
    }

    /**
     * Update an existing amenity.
     */
    public function update(UpdateAmenityRequest $request, Amenity $amenity): JsonResponse
    {
        $amenity->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $amenity,
            'message' => 'Amenity updated successfully.',
        ]);
    }

    /**
     * Remove an amenity.
     */
    public function destroy(Amenity $amenity): JsonResponse
    {
        $amenity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Amenity deleted successfully.',
        ], 204);
    }

    /**
     * List all amenities without pagination (useful for filters, dropdowns).
     */
    public function all(): JsonResponse
    {
        $amenities = Amenity::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $amenities,
        ]);
    }
}
