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
    // Sign-in (auth:sanctum) is applied by the route group in routes/api.php.
    public function __construct(private BookingManager $bookingManager) {}

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
        try {
            $refund = $this->bookingManager->cancel($id, Auth::id(), request()->string('reason')->toString() ?: null);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'refund' => [
                'percent' => $refund['percent'],
                'amount' => $refund['amount'],
                'currency' => $refund['currency'],
                'summary' => $refund['summary'],
            ],
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
