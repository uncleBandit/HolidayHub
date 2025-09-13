<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    /**
     * List packages with search, filter, sort, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Package::query();

        // Search by title, destination, or country
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Filter by price
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Filter by tags
        if ($tags = $request->input('tags')) {
            $query->whereJsonContains('tags', $tags);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $packages = $query->paginate($request->get('per_page', 12));

        return response()->json([
            'data' => PackageResource::collection($packages),
            'meta' => [
                'total' => $packages->total(),
                'per_page' => $packages->perPage(),
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new package.
     */

    public function store(StorePackageRequest $request, PackageService $packageService): JsonResponse
    {
    $package = $packageService->create($request->validated());

    return response()->json([
        'data' => new PackageResource($package),
        'message' => 'Package created successfully.',
    ], 201);
    }

    /**
     * Display a single package.
     */
    public function show(Package $package): JsonResponse
    {
        return response()->json([
            'data' => new PackageResource($package),
        ]);
    }

    /**
     * Update an existing package.
     */
    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $package->update($request->validated());

        return response()->json([
            'data' => new PackageResource($package),
            'message' => 'Package updated successfully.',
        ]);
    }

    /**
     * Delete a package.
     */
    public function destroy(Package $package): JsonResponse
    {
        $package->delete();

        return response()->json([
            'message' => 'Package deleted successfully.',
        ], 204);
    }
}
