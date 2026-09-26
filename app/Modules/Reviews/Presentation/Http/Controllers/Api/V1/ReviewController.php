<?php

namespace App\Modules\Reviews\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Reviews\Domain\Models\Review;
use App\Modules\Reviews\Presentation\Http\Requests\StoreReviewRequest;
use App\Modules\Reviews\Presentation\Http\Requests\UpdateReviewRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for a specific reviewable resource.
     *
     * @param  string  $type  The type of resource (e.g., 'hotel', 'destination', 'activity').
     * @param  int  $id  The ID of the resource.
     */
    public function index(string $type, int $id): JsonResponse
    {
        $reviewableClass = 'App\\Models\\'.ucfirst($type);

        if (! class_exists($reviewableClass)) {
            return response()->json(['error' => 'Invalid reviewable type.'], 404);
        }

        $reviewable = $reviewableClass::findOrFail($id);
        $reviews = $reviewable->reviews()
            ->where('status', 'approved')
            ->with('guest')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            // Get the reviewable model (e.g., Hotel, Destination, Activity)
            $reviewableClass = 'App\\Models\\'.ucfirst($request->validated('reviewable_type'));
            $reviewable = $reviewableClass::findOrFail($request->validated('reviewable_id'));

            // Ensure the user hasn't already reviewed this resource
            if (Auth::user()->reviews()->where('reviewable_id', $reviewable->id)->where('reviewable_type', $reviewableClass)->exists()) {
                return response()->json(['error' => 'You have already reviewed this resource.'], 409);
            }

            $review = $reviewable->reviews()->create([
                'guest_id' => Auth::id(),
                'rating' => $request->validated('rating'),
                'title' => $request->validated('title'),
                'comment' => $request->validated('comment'),
                'type' => $request->validated('reviewable_type'), // Explicitly setting the type
                'status' => 'pending', // Reviews are pending approval by default
                'meta' => $request->validated('meta', []),
            ]);

            return response()->json([
                'message' => 'Review submitted successfully! It will be visible after moderation.',
                'review' => $review,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to submit review. Please try again later.'], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): JsonResponse
    {
        return response()->json($review->load('guest', 'reviewable'));
    }

    /**
     * Update the specified review in storage.
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        if (Auth::id() !== $review->guest_id) {
            return response()->json(['error' => 'You are not authorized to update this review.'], 403);
        }

        $review->update($request->validated());

        return response()->json([
            'message' => 'Review updated successfully.',
            'review' => $review,
        ]);
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(Review $review): JsonResponse
    {
        if (Auth::id() !== $review->guest_id && ! Auth::user()->is_admin) {
            return response()->json(['error' => 'You are not authorized to delete this review.'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully.']);
    }

    // Admins can approve reviews
    public function approve(Review $review): JsonResponse
    {
        // Add a check to ensure the user is an admin
        if (! Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $review->update(['status' => 'approved']);

        return response()->json(['message' => 'Review approved successfully!']);
    }
}
