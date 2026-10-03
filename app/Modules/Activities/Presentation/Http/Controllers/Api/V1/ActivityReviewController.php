<?php

namespace App\Modules\Activities\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivityReviewRequest;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityReviewController extends Controller
{
    public function index(Activity $activity): JsonResponse
    {
        abort_unless($activity->isPublished(), 404);

        return response()->json([
            'data' => $activity->reviews()
                ->where('status', 'approved')
                ->with('guest.user')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(StoreActivityReviewRequest $request, Activity $activity): JsonResponse
    {
        abort_unless($activity->isPublished(), 404);
        $guest = $request->user()->guest;
        if (! $guest) {
            throw ValidationException::withMessages(['guest' => 'Complete your guest profile before reviewing an activity.']);
        }

        $validated = $request->validated();

        $review = DB::transaction(function () use ($activity, $guest, $validated): Review {
            $booking = Booking::query()
                ->whereKey($validated['booking_id'])
                ->where('guest_id', $guest->id)
                ->whereNotNull('activity_session_id')
                ->where('status', 'checked_out')
                ->lockForUpdate()
                ->first();

            if (! $booking
                || $booking->activitySession?->activity_id !== $activity->id
                || $booking->activitySession?->ends_at?->isFuture()) {
                throw ValidationException::withMessages([
                    'booking_id' => 'A completed booking for this activity is required to submit a review.',
                ]);
            }

            if (Review::query()->where('booking_id', $booking->id)->exists()) {
                throw ValidationException::withMessages([
                    'booking_id' => 'This booking already has a review.',
                ]);
            }

            return Review::create([
                'booking_id' => $booking->id,
                'guest_id' => $guest->id,
                'reviewable_type' => $activity->getMorphClass(),
                'reviewable_id' => $activity->id,
                'type' => 'activity',
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'comment' => $validated['comment'],
                'status' => 'pending',
            ]);
        });

        return response()->json(['data' => $review], 201);
    }

    public function moderate(Request $request, Review $review): JsonResponse
    {
        abort_unless($request->user()->can('reviews.moderate'), 403);
        $validated = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        abort_unless(
            $review->reviewable_type === 'activity'
                && Activity::query()->whereKey($review->reviewable_id)->exists(),
            404
        );

        $review->update(['status' => $validated['status']]);
        $activity = Activity::findOrFail($review->reviewable_id);
        $activity->forceFill([
            'rating' => $activity->reviews()->where('status', 'approved')->avg('rating') ?? 0,
            'reviews_count' => $activity->reviews()->where('status', 'approved')->count(),
        ])->saveQuietly();

        return response()->json(['data' => $review->fresh()]);
    }
}
