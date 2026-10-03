<?php

namespace App\Modules\Accommodation\Presentation\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Audit\Application\Services\AuditRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccommodationWorkflowController extends Controller
{
    public function __construct(private readonly AccommodationPublicationService $publicationService) {}

    public function submit(Request $request, Accommodation $accommodation): JsonResponse
    {
        $this->authorize('update', $accommodation);
        $accommodation = $this->publicationService->submitForReview($accommodation);

        return response()->json(['data' => $accommodation]);
    }

    public function approve(Request $request, Accommodation $accommodation): JsonResponse
    {
        $this->authorize('approve', $accommodation);
        $accommodation = DB::transaction(function () use ($accommodation, $request) {
            $before = ['status' => $accommodation->status?->value];
            $accommodation = $this->publicationService->approve($accommodation, $request->user()->id);
            app(AuditRecorder::class)->record('accommodations.published', $accommodation, $request->user(), before: $before, after: ['status' => $accommodation->status?->value]);

            return $accommodation;
        });

        return response()->json(['data' => $accommodation]);
    }

    public function reject(Request $request, Accommodation $accommodation): JsonResponse
    {
        $this->authorize('reject', $accommodation);
        $validated = $request->validate(['reason' => 'required|string|min:10|max:1000']);
        $accommodation = DB::transaction(function () use ($accommodation, $request, $validated) {
            $before = ['status' => $accommodation->status?->value];
            $accommodation = $this->publicationService->reject($accommodation, $request->user()->id, $validated['reason']);
            app(AuditRecorder::class)->record('accommodations.rejected', $accommodation, $request->user(), before: $before, after: ['status' => $accommodation->status?->value], reason: $validated['reason']);

            return $accommodation;
        });

        return response()->json(['data' => $accommodation]);
    }

    public function suspend(Request $request, Accommodation $accommodation): JsonResponse
    {
        $this->authorize('suspend', $accommodation);
        $validated = $request->validate(['reason' => 'required|string|min:10|max:1000']);
        $accommodation = DB::transaction(function () use ($accommodation, $request, $validated) {
            $before = ['status' => $accommodation->status?->value];
            $accommodation = $this->publicationService->suspend($accommodation, $request->user()->id, $validated['reason']);
            app(AuditRecorder::class)->record('accommodations.suspended', $accommodation, $request->user(), before: $before, after: ['status' => $accommodation->status?->value], reason: $validated['reason']);

            return $accommodation;
        });

        return response()->json(['data' => $accommodation]);
    }
}
