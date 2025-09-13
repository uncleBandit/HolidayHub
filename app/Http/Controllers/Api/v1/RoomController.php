<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms with filtering, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Room::query();

        // Filter by hotel
        if ($hotelId = $request->input('hotel_id')) {
            $query->where('hotel_id', $hotelId);
        }

        // Filter by room type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by price range
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $rooms = $query->paginate($request->input('per_page', 15))->withQueryString();

        return response()->json($rooms);
    }

    /**
     * Store a newly created room.
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = Room::create($request->validated());

        return response()->json([
            'data' => $room,
            'message' => 'Room created successfully.',
        ], 201);
    }

    /**
     * Display a specific room.
     */
    public function show(Room $room): JsonResponse
    {
        return response()->json([
            'data' => $room,
        ]);
    }

    /**
     * Update a specific room.
     */
    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $room->update($request->validated());

        return response()->json([
            'data' => $room,
            'message' => 'Room updated successfully.',
        ]);
    }

    /**
     * Delete a specific room.
     */
    public function destroy(Room $room): JsonResponse
    {
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully.',
        ], 204);
    }
}
