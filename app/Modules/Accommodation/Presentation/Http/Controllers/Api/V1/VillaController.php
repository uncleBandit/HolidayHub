<?php

namespace App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Accommodation\Presentation\Http\Requests\StoreVillaRequest;
use App\Modules\Accommodation\Presentation\Http\Requests\UpdateVillaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VillaController extends Controller
{
    /**
     * List all villas with filtering, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Villa::query();

        // Search by name, location, or amenities
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereJsonContains('amenities', $search);
            });
        }

        // Filter by price range
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by number of bedrooms
        if ($bedrooms = $request->input('bedrooms')) {
            $query->where('bedrooms', $bedrooms);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $villas = $query->paginate($request->input('per_page', 12))->withQueryString();

        return response()->json($villas);
    }

    /**
     * Store a new villa.
     */
    public function store(StoreVillaRequest $request): JsonResponse
    {
        $villa = Villa::create($request->validated());

        return response()->json([
            'data' => $villa,
            'message' => 'Villa created successfully.',
        ], 201);
    }

    /**
     * Show a single villa.
     */
    public function show(Villa $villa): JsonResponse
    {
        return response()->json([
            'data' => $villa,
        ]);
    }

    /**
     * Update an existing villa.
     */
    public function update(UpdateVillaRequest $request, Villa $villa): JsonResponse
    {
        $villa->update($request->validated());

        return response()->json([
            'data' => $villa,
            'message' => 'Villa updated successfully.',
        ]);
    }

    /**
     * Delete a villa.
     */
    public function destroy(Villa $villa): JsonResponse
    {
        $villa->delete();

        return response()->json([
            'message' => 'Villa deleted successfully.',
        ], 204);
    }
}
