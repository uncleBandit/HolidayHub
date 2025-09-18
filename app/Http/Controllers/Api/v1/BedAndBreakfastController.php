<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BedAndBreakfast;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBedAndBreakfastRequest;
use App\Http\Requests\UpdateBedAndBreakfastRequest;
use Illuminate\Http\JsonResponse;

class BedAndBreakfastController extends Controller
{
    /**
     * Display a paginated listing of B&Bs, with optional search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BedAndBreakfast::query();

        // Optional search by name or location
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Optional price filter
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Optional rating filter
        if ($rating = $request->input('rating')) {
            $query->where('rating', '>=', $rating);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $b&bs = $query->paginate($request->input('per_page', 15))->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $b&bs,
        ]);
    }

    /**
     * Store a new Bed & Breakfast.
     */
    public function store(StoreBedAndBreakfastRequest $request): JsonResponse
    {
        $b&b = BedAndBreakfast::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $b&b,
            'message' => 'Bed & Breakfast created successfully.',
        ], 201);
    }

    /**
     * Show a single Bed & Breakfast.
     */
    public function show(BedAndBreakfast $bedAndBreakfast): JsonResponse
    {
        if (!$bedAndBreakfast->is_active || !$bedAndBreakfast->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'This B&B is not available for booking.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $bedAndBreakfast->load([
                'amenities',
                'features',
                'offers',
                'reviews.guest',
                'availabilities',
                'seasonalRates',
            ]),
        ]);
    }


    /**
     * Update an existing Bed & Breakfast.
     */
    public function update(UpdateBedAndBreakfastRequest $request, BedAndBreakfast $bedAndBreakfast): JsonResponse
    {
        $bedAndBreakfast->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $bedAndBreakfast,
            'message' => 'Bed & Breakfast updated successfully.',
        ]);
    }

    /**
     * Remove a Bed & Breakfast.
     */
    public function destroy(BedAndBreakfast $bedAndBreakfast): JsonResponse
    {
        $bedAndBreakfast->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bed & Breakfast deleted successfully.',
        ], 204);
    }

    /**
     * List all B&Bs without pagination (for dropdowns, filters, or preloading).
     */
    public function all(): JsonResponse
    {
        $b&bs = BedAndBreakfast::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $b&bs,
        ]);
    }
}
