<?php

namespace App\Modules\Media\Presentation\Livewire;

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
        $post = MediaPost::query()
            ->where('status', MediaPostStatus::PendingReview->value)
            ->findOrFail($postId);

        $post->forceFill([
            'status' => MediaPostStatus::Published,
            'published_at' => now(),
            'reviewed_by' => Auth::id(),
            'moderation_notes' => null,
        ])->save();
    }

    public function reject(int $postId): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:1000'],
        ]);

        $post = MediaPost::query()
            ->where('status', MediaPostStatus::PendingReview->value)
            ->findOrFail($postId);

        $post->forceFill([
            'status' => MediaPostStatus::Rejected,
            'published_at' => null,
            'reviewed_by' => Auth::id(),
            'moderation_notes' => $this->rejectionReason,
        ])->save();

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
