<?php

namespace App\Modules\Reviews\Application\Services;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Reviews\Domain\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewModerationService
{
    public function approve(Review $review, User $moderator, ?string $reason = null): Review
    {
        return $this->decide($review, $moderator, 'approved', $reason);
    }

    public function reject(Review $review, User $moderator, string $reason): Review
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return $this->decide($review, $moderator, 'rejected', $reason);
    }

    private function decide(Review $review, User $moderator, string $status, ?string $reason): Review
    {
        return DB::transaction(function () use ($review, $moderator, $status, $reason): Review {
            $locked = Review::query()->lockForUpdate()->findOrFail($review->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'review' => 'Only pending reviews can be moderated.',
                ]);
            }

            $locked->load('reviewable');
            $locked->update(['status' => $status]);

            if ($locked->reviewable instanceof Activity) {
                $locked->reviewable->forceFill([
                    'rating' => $locked->reviewable->reviews()->where('status', 'approved')->avg('rating') ?? 0,
                    'reviews_count' => $locked->reviewable->reviews()->where('status', 'approved')->count(),
                ])->saveQuietly();
            } elseif ($locked->reviewable instanceof Accommodation) {
                $locked->reviewable->forceFill([
                    'avg_rating' => $locked->reviewable->reviews()->where('status', 'approved')->avg('rating') ?? 0,
                    'reviews_count' => $locked->reviewable->reviews()->where('status', 'approved')->count(),
                ])->saveQuietly();
            }

            app(AuditRecorder::class)->record(
                'reviews.'.$status,
                $locked,
                $moderator,
                before: ['status' => 'pending'],
                after: ['status' => $status],
                reason: $reason,
            );

            return $locked->refresh();
        });
    }
}
