<?php

namespace App\Modules\Activities\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Application\Services\ActivityPublicationService;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Audit\Application\Services\AuditRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityWorkflowController extends Controller
{
    public function __construct(private readonly ActivityPublicationService $publicationService) {}

    public function submit(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('submit', $activity);

        return response()->json(['data' => $this->publicationService->submit($activity)]);
    }

    public function review(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('moderate', $activity);

        $updated = DB::transaction(function () use ($activity, $request) {
            $before = ['status' => $activity->status?->value];
            $updated = $this->publicationService->markUnderReview($activity, $request->user()->id);
            app(AuditRecorder::class)->record('activities.review_started', $updated, $request->user(), before: $before, after: ['status' => $updated->status?->value]);

            return $updated;
        });

        return response()->json(['data' => $updated]);
    }

    public function approve(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('approve', Activity::class);
        $published = DB::transaction(function () use ($activity, $request) {
            $before = ['status' => $activity->status?->value];
            $approved = $this->publicationService->approve($activity, $request->user()->id);
            $published = $this->publicationService->publish($approved);
            app(AuditRecorder::class)->record('activities.published', $published, $request->user(), before: $before, after: ['status' => $published->status?->value]);

            return $published;
        });

        return response()->json(['data' => $published]);
    }

    public function reject(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('reject', Activity::class);
        $validated = $request->validate(['reason' => 'required|string|min:10|max:1000']);

        $updated = DB::transaction(function () use ($activity, $request, $validated) {
            $before = ['status' => $activity->status?->value];
            $updated = $this->publicationService->reject($activity, $request->user()->id, $validated['reason']);
            app(AuditRecorder::class)->record('activities.rejected', $updated, $request->user(), before: $before, after: ['status' => $updated->status?->value], reason: $validated['reason']);

            return $updated;
        });

        return response()->json(['data' => $updated]);
    }

    public function suspend(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('suspend', Activity::class);
        $validated = $request->validate(['reason' => 'required|string|min:10|max:1000']);

        $updated = DB::transaction(function () use ($activity, $request, $validated) {
            $before = ['status' => $activity->status?->value];
            $updated = $this->publicationService->suspend($activity, $request->user()->id, $validated['reason']);
            app(AuditRecorder::class)->record('activities.suspended', $updated, $request->user(), before: $before, after: ['status' => $updated->status?->value], reason: $validated['reason']);

            return $updated;
        });

        return response()->json(['data' => $updated]);
    }
}
