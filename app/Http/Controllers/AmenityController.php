<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAmenityRequest;
use App\Http\Requests\UpdateAmenityRequest;

class AmenityController extends Controller
{
    /**
     * Display a listing of amenities (paginated).
     */
    public function index()
    {
        $amenities = Amenity::latest()->paginate(15);

        return response()->json($amenities);
    }

    /**
     * Store a newly created amenity in storage.
     */
    public function store(StoreAmenityRequest $request)
    {
        $amenity = Amenity::create($request->validated());

        return response()->json([
            'success' => true,
            'amenity' => $amenity,
            'message' => 'Amenity created successfully.',
        ], 201);
    }

    /**
     * Display the specified amenity.
     */
    public function show(Amenity $amenity)
    {
        return response()->json($amenity);
    }

    /**
     * Update the specified amenity.
     */
    public function update(UpdateAmenityRequest $request, Amenity $amenity)
    {
        $amenity->update($request->validated());

        return response()->json([
            'success' => true,
            'amenity' => $amenity,
            'message' => 'Amenity updated successfully.',
        ]);
    }

    /**
     * Remove the specified amenity.
     */
    public function destroy(Amenity $amenity)
    {
        $amenity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Amenity deleted successfully.',
        ]);
    }

    /**
     * Optional: List all amenities without pagination (useful for dropdowns or filters).
     */
    public function all()
    {
        $amenities = Amenity::all();

        return response()->json($amenities);
    }
}
