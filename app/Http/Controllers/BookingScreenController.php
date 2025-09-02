<?php


namespace App\Http\Controllers;


use App\Models\Booking;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Inertia\Inertia;


class BookingScreenController extends Controller
{
public function index(Request $request)
{
$user = $request->user();


// Pre-fill guest info if available
$guest = optional($user->guest ?? null)->only([
'first_name','last_name','email','phone','date_of_birth','nationality'
]);


// Keep payload light but useful
$hotels = Hotel::query()
->with(['rooms:id,hotel_id,name,price_per_night,max_adults,max_children'])
->select('id','name','city','country','cover_image')
->orderBy('name')
->take(50)
->get();


// Recent bookings to show context/quick links
$myBookings = Booking::query()
->where('user_id', $user->id)
->latest('created_at')
->take(5)
->get(['id','hotel_id','check_in_date','check_out_date','status','total_amount','currency']);


return Inertia::render('Booking/Index', [
'guest' => $guest,
'hotels' => $hotels,
'myBookings' => $myBookings,
'paymentOptions' => ['card','paypal','mpesa','wallet'],
'currencyOptions' => ['USD','EUR','GBP','KES'],
]);
}
}
