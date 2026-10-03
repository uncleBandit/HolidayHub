<?php

namespace App\Modules\Activities\Application\Services;

use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class ActivityPublicationService
{
    public function submit(Activity $activity): Activity
    {
        $this->ensurePublishable($activity);

        if (! in_array($activity->status, [ActivityStatus::Draft, ActivityStatus::Rejected], true)) {
            throw new LogicException('Only draft or rejected activities can be submitted for review.');
        }

        return DB::transaction(function () use ($activity): Activity {
            $activity->forceFill([
                'status' => ActivityStatus::Submitted,
                'verification_status' => ActivityVerificationStatus::Pending,
                'submitted_at' => now(),
                'rejection_reason' => null,
                'is_active' => false,
            ])->save();

            $activity->verificationHistory()->create([
                'status' => ActivityVerificationStatus::Pending->value,
                'submitted_at' => now(),
                'notes' => 'Provider submitted activity for review.',
            ]);

            return $activity->refresh();
        });
    }

    public function markUnderReview(Activity $activity, int $reviewerId): Activity
    {
        if ($activity->status !== ActivityStatus::Submitted) {
            throw new LogicException('Only submitted activities can be moved into review.');
        }

        return $this->recordDecision($activity, ActivityStatus::UnderReview, ActivityVerificationStatus::Pending, $reviewerId, null);
    }

    public function approve(Activity $activity, int $reviewerId): Activity
    {
        if (! in_array($activity->status, [ActivityStatus::Submitted, ActivityStatus::UnderReview], true)) {
            throw new LogicException('Only submitted activities can be approved.');
        }

        $this->ensurePublishable($activity);

        return $this->recordDecision($activity, ActivityStatus::Approved, ActivityVerificationStatus::Approved, $reviewerId, null);
    }

    public function publish(Activity $activity): Activity
    {
        if ($activity->status !== ActivityStatus::Approved
            || $activity->verification_status !== ActivityVerificationStatus::Approved) {
            throw new LogicException('An activity must be approved before it can be published.');
        }

        return DB::transaction(function () use ($activity): Activity {
            $activity->forceFill([
                'status' => ActivityStatus::Published,
                'is_active' => true,
                'published_at' => now(),
            ])->save();

            return $activity->refresh();
        });
    }

    public function reject(Activity $activity, int $reviewerId, string $reason): Activity
    {
        return $this->recordDecision($activity, ActivityStatus::Rejected, ActivityVerificationStatus::Rejected, $reviewerId, $reason);
    }

    public function suspend(Activity $activity, int $reviewerId, string $reason): Activity
    {
        if ($activity->status !== ActivityStatus::Published) {
            throw new LogicException('Only published activities can be suspended.');
        }

        return $this->recordDecision($activity, ActivityStatus::Suspended, ActivityVerificationStatus::Suspended, $reviewerId, $reason);
    }

    public function archive(Activity $activity): Activity
    {
        return DB::transaction(function () use ($activity): Activity {
            $activity->forceFill([
                'status' => ActivityStatus::Archived,
                'is_active' => false,
            ])->save();

            return $activity->refresh();
        });
    }

    private function recordDecision(
        Activity $activity,
        ActivityStatus $status,
        ActivityVerificationStatus $verificationStatus,
        int $reviewerId,
        ?string $reason
    ): Activity {
        return DB::transaction(function () use ($activity, $status, $verificationStatus, $reviewerId, $reason): Activity {
            $now = now();
            $activity->forceFill([
                'status' => $status,
                'verification_status' => $verificationStatus,
                'reviewed_by' => $reviewerId,
                'verified_at' => $verificationStatus === ActivityVerificationStatus::Approved ? $now : null,
                'rejection_reason' => $status === ActivityStatus::Rejected ? $reason : null,
                'suspension_reason' => $status === ActivityStatus::Suspended ? $reason : null,
                'is_active' => false,
            ])->save();

            $activity->verificationHistory()->create([
                'reviewer_id' => $reviewerId,
                'status' => $verificationStatus->value,
                'reviewed_at' => $now,
                'notes' => $reason,
            ]);

            return $activity->refresh();
        });
    }

    private function ensurePublishable(Activity $activity): void
    {
        $activity->load(['category', 'images', 'options', 'sessions', 'schedules']);

        $missing = [];
        foreach ([
            'provider_id' => $activity->provider_id !== null,
            'destination_id' => $activity->destination_id !== null,
            'category' => $activity->category?->is_active === true,
            'description' => filled($activity->description) && mb_strlen($activity->description) >= 30,
            'duration' => $activity->duration_minutes !== null && $activity->duration_minutes > 0,
            'cover image' => $activity->images->isNotEmpty() || filled($activity->thumbnail),
            'option or base price' => $activity->options->where('is_active', true)->isNotEmpty()
                || ($activity->base_price !== null && (float) $activity->base_price >= 0),
            'schedule or session' => $activity->schedules->where('is_active', true)->isNotEmpty()
                || $activity->sessions->isNotEmpty(),
        ] as $field => $valid) {
            if (! $valid) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'activity' => 'Complete these required details before submitting: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
