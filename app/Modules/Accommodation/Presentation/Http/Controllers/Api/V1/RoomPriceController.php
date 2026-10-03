<?php

namespace App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accommodation\Domain\Models\RoomPrice;
use App\Modules\Accommodation\Presentation\Http\Requests\StoreRoomPriceRequest;
use App\Modules\Accommodation\Presentation\Http\Requests\UpdateRoomPriceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomPriceController extends Controller
{
    /**
     * Display a listing of room prices with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RoomPrice::query();
        $user = $request->user();

        if (! $user->isPlatformAdmin()) {
            $query->whereHas('room.hotel.accommodation', fn ($accommodation) => $accommodation
                ->where('provider_id', $user->provider?->id));
        }

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
        $sortBy = in_array($request->input('sort_by'), ['start_date', 'end_date', 'base_price', 'created_at'], true)
            ? $request->input('sort_by')
            : 'start_date';
        $sortDir = in_array($request->input('sort_dir'), ['asc', 'desc'], true)
            ? $request->input('sort_dir')
            : 'asc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $roomPrices = $query->paginate(min(max((int) $request->input('per_page', 15), 1), 100))->withQueryString();

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
        $this->authorize('view', $roomPrice);

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
        $this->authorize('delete', $roomPrice);
        $roomPrice->delete();

        return response()->json([
            'message' => 'Room price deleted successfully.',
        ], 204);
    }
}
