<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Hotel;
use App\Services\BookingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function __construct(private BookingManager $bookingManager) {}

    
    /**
     * Create a new booking.
     */
    public function store(StoreBookingRequest $request): BookingResource|JsonResponse
    {
        try {
            $booking = $this->bookingManager->create($request->validated(), Auth::id());
            return new BookingResource($booking);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel an existing booking.
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $this->bookingManager->cancel($id, Auth::id());
            return response()->json(['message' => 'Booking cancelled successfully.']);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to cancel booking.'], 500);
        }
    }

    /**
     * List authenticated user's bookings.
     */
    public function myBookings(): JsonResponse
    {
        $bookings = $this->bookingManager->forUser(Auth::id())->paginate(15);
        return response()->json(BookingResource::collection($bookings));
    }
}
