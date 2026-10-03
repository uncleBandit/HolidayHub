<?php

namespace App\Modules\Activities\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Application\Services\ActivitySearchQuery;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivityRequest;
use App\Modules\Activities\Presentation\Http\Requests\UpdateActivityRequest;
use App\Modules\Activities\Presentation\Http\Resources\ActivityResource;
use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index(Request $request, ActivitySearchQuery $search): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'category_id' => ['nullable', 'integer', 'exists:activity_categories,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],
            'min_duration' => ['nullable', 'integer', 'min:1'],
            'max_duration' => ['nullable', 'integer', 'gte:min_duration'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'booking_mode' => ['nullable', 'in:shared,private,request,instant'],
            'language' => ['nullable', 'string', 'max:12'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'sort' => ['nullable', 'in:price_low_high,price_high_low,rating,latest,popularity'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $activities = $search->build($filters)->paginate($filters['per_page'] ?? 12)->withQueryString();

        return ActivityResource::collection($activities)->response();
    }

    public function store(StoreActivityRequest $request): JsonResponse
    {
        $provider = $request->user()->provider;
        abort_unless($provider, 403, 'An active provider profile is required to create an activity.');

        $activity = Activity::create([
            ...$request->validated(),
            'provider_id' => $provider->id,
        ]);

        Log::info('Activity draft created.', [
            'activity_id' => $activity->id,
            'provider_id' => $provider->id,
        ]);

        return (new ActivityResource($activity->load(['category', 'destination', 'images'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $activity): ActivityResource
    {
        $query = Activity::published();

        $activity = ctype_digit($activity)
            ? $query->findOrFail((int) $activity)
            : $query->where('slug', $activity)->firstOrFail();

        $activity->load([
            'category',
            'destination',
            'provider',
            'images',
            'amenities',
            'options' => fn ($query) => $query->where('is_active', true),
            'locations',
            'itinerary',
            'requirements',
            'languages',
            'mediaPosts' => fn ($query) => $query
                ->publiclyPublished()
                ->latest('published_at')
                ->limit(10)
                ->with(['assets' => fn ($assets) => $assets->where('status', MediaAssetStatus::Ready->value)]),
            'sessions' => fn ($query) => $query->bookable()
                ->where('starts_at', '>=', now()->addMinutes($activity->minimum_notice_minutes))
                ->orderBy('starts_at')
                ->limit(20),
            'reviews' => fn ($query) => $query->where('status', 'approved')->latest()->limit(10),
        ]);

        return new ActivityResource($activity);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): ActivityResource
    {
        $activity->update($request->validated());

        Log::info('Activity updated.', [
            'activity_id' => $activity->id,
            'provider_id' => $activity->provider_id,
        ]);

        return new ActivityResource($activity->fresh(['category', 'destination', 'images']));
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $this->authorize('delete', $activity);
        $activity->delete();

        Log::notice('Activity archived by soft delete.', ['activity_id' => $activity->id]);

        return response()->json(['message' => 'Activity archived.']);
    }
}
