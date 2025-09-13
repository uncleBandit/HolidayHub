<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\RoomPrice;
use App\Http\Requests\StoreRoomPriceRequest;
use App\Http\Requests\UpdateRoomPriceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomPriceController extends Controller
{
    /**
     * Display a listing of room prices with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RoomPrice::query();

        // Filter by room ID
        if ($roomId = $request->input('room_id')) {
            $query->where('room_id', $roomId);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->where('start_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->where('end_date', '<=', $endDate);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'start_date');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $roomPrices = $query->paginate($request->input('per_page', 15))->withQueryString();

        return response()->json($roomPrices);
    }

    /**
     * Store a newly created room price.
     */
    public function store(StoreRoomPriceRequest $request): JsonResponse
    {
        $roomPrice = RoomPrice::create($request->validated());

        return response()->json([
            'data' => $roomPrice,
            'message' => 'Room price created successfully.',
        ], 201);
    }

    /**
     * Display the specified room price.
     */
    public function show(RoomPrice $roomPrice): JsonResponse
    {
        return response()->json([
            'data' => $roomPrice,
        ]);
    }

    /**
     * Update the specified room price.
     */
    public function update(UpdateRoomPriceRequest $request, RoomPrice $roomPrice): JsonResponse
    {
        $roomPrice->update($request->validated());

        return response()->json([
            'data' => $roomPrice,
            'message' => 'Room price updated successfully.',
        ]);
    }

    /**
     * Remove the specified room price.
     */
    public function destroy(RoomPrice $roomPrice): JsonResponse
    {
        $roomPrice->delete();

        return response()->json([
            'message' => 'Room price deleted successfully.',
        ], 204);
    }
}
