<?php

namespace App\Modules\Activities\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Activities\Application\Services\ActivityScheduleGenerator;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySchedule;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivityOptionRequest;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivityScheduleRequest;
use App\Modules\Activities\Presentation\Http\Requests\StoreActivitySessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityInventoryController extends Controller
{
    public function sessions(Activity $activity): JsonResponse
    {
        abort_unless($activity->isPublished(), 404);

        return response()->json([
            'data' => $activity->sessions()
                ->bookable()
                ->where('starts_at', '>=', now()->addMinutes($activity->minimum_notice_minutes))
                ->orderBy('starts_at')
                ->limit(100)
                ->get()
                ->map(fn (ActivitySession $session) => $this->sessionData($session)),
        ]);
    }

    public function storeOption(StoreActivityOptionRequest $request, Activity $activity): JsonResponse
    {
        $option = DB::transaction(function () use ($request, $activity) {
            $option = $activity->options()->create($request->validated());
            if ($activity->isPublished()) {
                $activity->forceFill([
                    'status' => ActivityStatus::Draft,
                    'verification_status' => ActivityVerificationStatus::Pending,
                    'is_active' => false,
                    'published_at' => null,
                ])->save();
            }

            return $option;
        });

        return response()->json(['data' => $option], 201);
    }

    public function storeSchedule(StoreActivityScheduleRequest $request, Activity $activity): JsonResponse
    {
        $schedule = $activity->schedules()->create($request->validated());

        return response()->json(['data' => $schedule], 201);
    }

    public function generateSessions(
        Request $request,
        Activity $activity,
        ActivitySchedule $schedule,
        ActivityScheduleGenerator $generator
    ): JsonResponse {
        $this->authorize('update', $activity);
        abort_unless($schedule->activity_id === $activity->id, 404);

        $dates = $request->validate([
            'from' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'through' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:'.now()->addYear()->toDateString()],
        ]);

        $sessions = $generator->generate(
            $schedule,
            now()->parse($dates['from']),
            now()->parse($dates['through'])
        );

        return response()->json([
            'data' => $sessions->map(fn (ActivitySession $session) => $this->sessionData($session)),
        ], 201);
    }

    public function storeSession(StoreActivitySessionRequest $request, Activity $activity): JsonResponse
    {
        $session = $activity->sessions()->create($request->validated());

        return response()->json(['data' => $this->sessionData($session)], 201);
    }

    private function sessionData(ActivitySession $session): array
    {
        return [
            'id' => $session->id,
            'starts_at' => $session->starts_at?->toIso8601String(),
            'ends_at' => $session->ends_at?->toIso8601String(),
            'timezone' => $session->timezone,
            'capacity' => $session->capacity,
            'available_capacity' => $session->availableCapacity(),
            'status' => $session->status?->value,
            'option_id' => $session->activity_option_id,
        ];
    }
}
