<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingScreenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // Ensure API authentication
    }

    /**
     * Return booking screen data for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Pre-fill guest info if available
        $guest = optional($user->guest)->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'date_of_birth',
            'nationality'
        ]) ?? null;

        // Top 50 hotels with essential room info
        $hotels = Hotel::query()
            ->with(['rooms:id,hotel_id,name,price_per_night,max_adults,max_children'])
            ->select('id', 'name', 'city', 'country', 'cover_image')
            ->orderBy('name')
            ->take(50)
            ->get();

        // Recent 5 bookings for quick reference
        $myBookings = Booking::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->take(5)
            ->get([
                'id',
                'hotel_id',
                'check_in_date',
                'check_out_date',
                'status',
                'total_amount',
                'currency'
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'guest' => $guest,
                'hotels' => $hotels,
                'recent_bookings' => $myBookings,
                'payment_options' => ['card', 'paypal', 'mpesa', 'wallet'],
                'currency_options' => ['USD', 'EUR', 'GBP', 'KES'],
            ]
        ]);
    }
}
