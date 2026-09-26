<?php

namespace App\Modules\Booking\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Application\Services\BookingManager;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Booking\Presentation\Http\Filters\BookingFilters;
use App\Modules\Booking\Presentation\Http\Requests\StoreBookingRequest;
use App\Modules\Booking\Presentation\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(private BookingManager $bookingManager)
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request, BookingFilters $filters): JsonResponse
    {
        $query = $this->bookingManager->forUser(Auth::id());
        $filters->apply($query, $request);

        $bookings = $query->orderBy(
            $request->input('sort_by', 'created_at'),
            $request->input('sort_dir', 'desc')
        )->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
        ]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('X-Idempotency-Key');
        $booking = $this->bookingManager->createWithPayment(
            $request->validated(),
            Auth::id(),
            $idempotencyKey
        );

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
            'message' => 'Booking created successfully.',
        ], 201);
    }

    public function cancel(int $id): JsonResponse
    {
        $this->bookingManager->cancel($id, Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingManager->find($id, Auth::id());

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    public function all(Request $request, BookingFilters $filters): JsonResponse
    {
        $this->authorize('viewAny', Booking::class);

        $query = $this->bookingManager->query();
        $filters->apply($query, $request);

        $bookings = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
        ]);
    }
}
