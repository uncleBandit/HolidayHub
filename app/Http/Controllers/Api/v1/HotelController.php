<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Services\HotelManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function __construct(private HotelManager $hotelManager) {}

    /**
     * List hotels with filters, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['location', 'price_min', 'price_max', 'rating', 'availability']);
        $sort = $request->get('sort', 'latest'); // default sort
        $perPage = $request->get('per_page', 15);

        $hotels = $this->hotelManager->getHotels($filters, $sort, $perPage);

        return response()->json([
            'data' => HotelResource::collection($hotels),
            'meta' => [
                'total' => $hotels->total(),
                'per_page' => $hotels->perPage(),
                'current_page' => $hotels->currentPage(),
                'last_page' => $hotels->lastPage(),
            ],
        ]);
    }

    /**
     * Store a new hotel (Admin only).
     */
    public function store(StoreHotelRequest $request): JsonResponse
    {
        $hotel = $this->hotelManager->create($request->validated());

        return response()->json([
            'data' => new HotelResource($hotel),
            'message' => 'Hotel created successfully.',
        ], 201);
    }

    /**
     * Show a single hotel.
     */
    public function show(string $slug): JsonResponse
    {
        $hotel = $this->hotelManager->findBySlug($slug);

        return response()->json([
            'data' => new HotelResource($hotel),
        ]);
    }

    /**
     * Update a hotel (Admin only).
     */
    public function update(UpdateHotelRequest $request, int $id): JsonResponse
    {
        $hotel = $this->hotelManager->update($id, $request->validated());

        return response()->json([
            'data' => new HotelResource($hotel),
            'message' => 'Hotel updated successfully.',
        ]);
    }

    /**
     * Delete a hotel (Admin only).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->hotelManager->delete($id);

        return response()->json([
            'message' => 'Hotel deleted successfully.',
        ], 204);
    }
}
