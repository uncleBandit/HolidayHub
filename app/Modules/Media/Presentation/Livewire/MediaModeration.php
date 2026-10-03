<?php

namespace App\Modules\Media\Presentation\Livewire;

use App\Modules\Media\Application\Services\MediaModerationService;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MediaModeration extends Component
{
    use WithPagination;

    public string $rejectionReason = '';

    public function approve(int $postId): void
    {
        abort_unless(Auth::user()?->can('media.moderate'), 403);
        $post = MediaPost::query()
            ->where('status', MediaPostStatus::PendingReview->value)
            ->findOrFail($postId);

        app(MediaModerationService::class)->approve($post, Auth::user());
    }

    public function reject(int $postId): void
    {
        abort_unless(Auth::user()?->can('media.moderate'), 403);
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $post = MediaPost::query()
            ->where('status', MediaPostStatus::PendingReview->value)
            ->findOrFail($postId);

        app(MediaModerationService::class)->reject($post, Auth::user(), $this->rejectionReason);

        $this->reset('rejectionReason');
    }

    public function render()
    {
        $posts = MediaPost::query()
            ->where('status', MediaPostStatus::PendingReview->value)
            ->with(['provider', 'assets'])
            ->orderBy('created_at')
            ->paginate(10);

        return view('livewire.media.moderation', compact('posts'));
    }
}
