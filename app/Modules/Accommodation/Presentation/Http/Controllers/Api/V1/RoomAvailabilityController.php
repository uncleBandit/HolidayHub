<?php

namespace App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accommodation\Presentation\Http\Requests\StoreRoomAvailabilityRequest;
use App\Modules\Accommodation\Presentation\Http\Requests\UpdateRoomAvailabilityRequest;
use App\Modules\Availability\Domain\Models\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomAvailabilityController extends Controller
{
    /**
     * Display a listing of room availability.
     */
    public function index(Request $request): JsonResponse
    {
        $availabilities = RoomAvailability::query()
            ->when($request->input('hotel_id'), fn ($q, $hotelId) => $q->where('hotel_id', $hotelId))
            ->when($request->input('room_type_id'), fn ($q, $roomTypeId) => $q->where('room_type_id', $roomTypeId))
            ->when($request->input('date'), fn ($q, $date) => $q->whereDate('available_from', '<=', $date)
                ->whereDate('available_to', '>=', $date))
            ->orderBy($request->input('sort_by', 'available_from'), $request->input('sort_dir', 'asc'))
            ->paginate(15);

        return response()->json($availabilities);
    }

    /**
     * Store a newly created room availability.
     */
    public function store(StoreRoomAvailabilityRequest $request): JsonResponse
    {
        $availability = RoomAvailability::create($request->validated());

        return response()->json([
            'data' => $availability,
            'message' => 'Room availability created successfully.',
        ], 201);
    }

    /**
     * Display the specified room availability.
     */
    public function show(RoomAvailability $roomAvailability): JsonResponse
    {
        return response()->json([
            'data' => $roomAvailability,
        ]);
    }

    /**
     * Update the specified room availability.
     */
    public function update(UpdateRoomAvailabilityRequest $request, RoomAvailability $roomAvailability): JsonResponse
    {
        $roomAvailability->update($request->validated());

        return response()->json([
            'data' => $roomAvailability,
            'message' => 'Room availability updated successfully.',
        ]);
    }

    /**
     * Remove the specified room availability.
     */
    public function destroy(RoomAvailability $roomAvailability): JsonResponse
    {
        $roomAvailability->delete();

        return response()->json([
            'message' => 'Room availability deleted successfully.',
        ], 204);
    }
}
