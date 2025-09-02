<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotelStoreRequest;
use App\Http\Requests\HotelUpdateRequest;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Http\Resources\HotelResource;
use App\Services\HotelManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HotelController extends Controller
{
    public function __construct(private HotelManager $hotelManager) {}

    /**
     * List hotels with filters (location, price, rating, availability).
     */
    public function index(Request $request)
    {
        $hotels = $this->hotelManager->getHotels($request->all());

        return Inertia::render('Hotels/Index', [
        'hotels' => HotelResource::collection($hotels),
    ]);
    }

    /**
     * Store a new hotel (Admin only).
     */
    public function store(StoreHotelRequest $request)
    {
        $hotel = $this->hotelManager->create($request->validated());

        return new HotelResource($hotel);
    }

    /**
     * Show a single hotel.
     */
    public function show(string $slug)
{
    // If your hotelManager can find by slug:
    $hotel = $this->hotelManager->findBySlug($slug);

    // If not, fallback to Eloquent:
    // $hotel = Hotel::where('slug', $slug)->firstOrFail();

    return Inertia::view('livewire.hotel.show', [
        'hotel' => new HotelResource($hotel),
    ]);
}


    /**
     * Update hotel.
     */
    public function update(UpdateHotelRequest $request, int $id)
    {
        $hotel = $this->hotelManager->update($id, $request->validated());

        return new HotelResource($hotel);
    }

    /**
     * Delete hotel.
     */
    public function destroy(int $id)
    {
        $this->hotelManager->delete($id);

        return response()->json(['message' => 'Hotel deleted successfully.']);
    }
}
