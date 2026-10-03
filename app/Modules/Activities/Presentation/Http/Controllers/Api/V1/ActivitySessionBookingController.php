<?php

namespace App\Modules\Activities\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Application\Services\ActivitySessionBookingService;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivityBookingRequest;
use App\Modules\Booking\Presentation\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;

class ActivitySessionBookingController extends Controller
{
    public function __construct(private readonly ActivitySessionBookingService $bookingService) {}

    public function store(
        StoreActivityBookingRequest $request,
        Activity $activity,
        ActivitySession $session
    ): JsonResponse {
        $booking = $this->bookingService->book(
            $activity,
            $session,
            $request->user(),
            $request->validated('participants'),
            $request->header('X-Idempotency-Key')
        );

        return response()->json([
            'data' => new BookingResource($booking->load(['bookable', 'activitySession', 'guest'])),
        ], 201);
    }
}
