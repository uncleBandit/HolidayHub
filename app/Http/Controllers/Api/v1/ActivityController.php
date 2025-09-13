<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    /**
     * Display a listing of activities with advanced filtering, sorting, and pagination.
     */
    public function index(Request $request)
    {
        $query = Activity::query();

        // ✅ Search by keyword
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }

        // ✅ Filter by destination
        if ($destination = $request->input('destination_id')) {
            $query->where('destination_id', $destination);
        }

        // ✅ Filter by category (e.g., Adventure, Culture, Family, Luxury)
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // ✅ Price range filtering
        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // ✅ Sorting (default: popularity)
        $sort = $request->input('sort', 'popularity');
        $query->when($sort === 'price_low_high', fn($q) => $q->orderBy('price', 'asc'))
              ->when($sort === 'price_high_low', fn($q) => $q->orderBy('price', 'desc'))
              ->when($sort === 'rating', fn($q) => $q->orderBy('average_rating', 'desc'))
              ->when($sort === 'latest', fn($q) => $q->latest())
              ->when($sort === 'popularity', fn($q) => $q->orderBy('bookings_count', 'desc'));

        // ✅ Pagination
        $activities = $query->paginate($request->input('per_page', 12));

        return ActivityResource::collection($activities);
    }

    /**
     * Store a newly created activity in storage.
     */
    public function store(StoreActivityRequest $request)
    {
        $activity = Activity::create($request->validated());

        Log::info('New activity created', ['activity_id' => $activity->id]);

        return new ActivityResource($activity);
    }

    /**
     * Display the specified activity with reviews and availability.
     */
    public function show(Activity $activity)
    {
        $activity->load(['destination', 'reviews.user']);

        return (new ActivityResource($activity))
            ->additional([
                'availability' => $activity->availability ?? [],
                'recommended' => Activity::where('destination_id', $activity->destination_id)
                    ->where('id', '!=', $activity->id)
                    ->inRandomOrder()
                    ->take(4)
                    ->get(),
            ]);
    }

    /**
     * Update the specified activity in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        $activity->update($request->validated());

        Log::info('Activity updated', ['activity_id' => $activity->id]);

        return new ActivityResource($activity);
    }

    /**
     * Remove the specified activity from storage.
     */
    public function destroy(Activity $activity)
    {
        $activity->delete();

        Log::warning('Activity deleted', ['activity_id' => $activity->id]);

        return response()->json(['message' => 'Activity deleted successfully']);
    }
}
