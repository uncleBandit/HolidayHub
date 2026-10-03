<?php

namespace App\Modules\Media\Application\Services;

use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MediaModerationService
{
    public function approve(MediaPost $post, User $reviewer): MediaPost
    {
        return $this->decide($post, $reviewer, MediaPostStatus::Published);
    }

    public function reject(MediaPost $post, User $reviewer, string $reason): MediaPost
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        return $this->decide($post, $reviewer, MediaPostStatus::Rejected, $reason);
    }

    private function decide(
        MediaPost $post,
        User $reviewer,
        MediaPostStatus $status,
        ?string $reason = null,
    ): MediaPost {
        return DB::transaction(function () use ($post, $reviewer, $status, $reason): MediaPost {
            $locked = MediaPost::query()->lockForUpdate()->findOrFail($post->id);
            if ($locked->status !== MediaPostStatus::PendingReview) {
                throw ValidationException::withMessages([
                    'post' => 'Only media awaiting moderation can be decided.',
                ]);
            }

            $from = $locked->status->value;
            $locked->forceFill([
                'status' => $status,
                'published_at' => $status === MediaPostStatus::Published ? now() : null,
                'reviewed_by' => $reviewer->id,
                'moderation_notes' => $reason,
            ])->save();

            app(AuditRecorder::class)->record(
                'media.'.$status->value,
                $locked,
                $reviewer,
                before: ['status' => $from],
                after: ['status' => $status->value],
                reason: $reason,
            );

            return $locked->refresh();
        });
    }
}
