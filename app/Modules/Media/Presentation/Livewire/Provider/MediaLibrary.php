<?php

namespace App\Modules\Media\Presentation\Livewire\Provider;

use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Enums\MediaPostType;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

class MediaLibrary extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public int $providerId;

    public string $type = 'reel';

    public string $title = '';

    public string $caption = '';

    public ?int $activityId = null;

    public ?TemporaryUploadedFile $video = null;

    public ?TemporaryUploadedFile $thumbnail = null;

    public function mount(): void
    {
        $provider = Auth::user()?->provider;
        abort_unless($provider, 403);

        $this->providerId = $provider->id;
    }

    public function publish(): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(array_column(MediaPostType::cases(), 'value'))],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'activityId' => [
                'nullable',
                'integer',
                Rule::exists('activities', 'id')
                    ->where('provider_id', $this->providerId)
                    ->whereNull('deleted_at'),
            ],
            'video' => ['required', 'file', 'max:10240', 'mimes:mp4,webm'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $disk = config('media.disk');
        $directory = 'media/provider-'.$this->providerId.'/'.now()->format('Y/m').'/'.Str::uuid();
        $extension = match ($this->video->guessExtension()) {
            'mp4' => 'mp4',
            'webm' => 'webm',
            default => throw new RuntimeException('The uploaded video type is not supported.'),
        };
        $videoPath = $this->video->storeAs($directory, 'original.'.$extension, $disk);

        if ($videoPath === false) {
            throw new RuntimeException('The uploaded video could not be stored.');
        }

        $storedPaths = [$videoPath];
        $thumbnailPath = null;

        if ($this->thumbnail) {
            $thumbnailPath = $this->thumbnail->storeAs(
                $directory,
                'thumbnail.'.($this->thumbnail->guessExtension() ?: 'jpg'),
                $disk,
            );

            if ($thumbnailPath === false) {
                Storage::disk($disk)->delete($storedPaths);
                throw new RuntimeException('The video thumbnail could not be stored.');
            }

            $storedPaths[] = $thumbnailPath;
        }

        try {
            DB::transaction(function () use ($validated, $disk, $videoPath, $thumbnailPath, $extension): void {
                $post = MediaPost::create([
                    'provider_id' => $this->providerId,
                    'type' => $validated['type'],
                    'title' => $validated['title'] ?: null,
                    'caption' => $validated['caption'] ?: null,
                    'status' => MediaPostStatus::PendingReview,
                    'visibility' => 'public',
                ]);

                if ($validated['activityId'] ?? null) {
                    $activity = Activity::query()
                        ->where('provider_id', $this->providerId)
                        ->findOrFail($validated['activityId']);
                    $post->targetable()->associate($activity);
                    $post->save();
                }

                $post->assets()->create([
                    'type' => MediaAssetType::Original,
                    'disk' => $disk,
                    'path' => $videoPath,
                    'mime_type' => $extension === 'mp4' ? 'video/mp4' : 'video/webm',
                    'size_bytes' => $this->video->getSize(),
                    'status' => MediaAssetStatus::Ready,
                ]);

                if ($thumbnailPath !== null && $this->thumbnail !== null) {
                    $post->assets()->create([
                        'type' => MediaAssetType::Thumbnail,
                        'disk' => $disk,
                        'path' => $thumbnailPath,
                        'mime_type' => $this->thumbnail->getMimeType(),
                        'size_bytes' => $this->thumbnail->getSize(),
                        'status' => MediaAssetStatus::Ready,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPaths);
            throw $exception;
        }

        $this->reset(['type', 'title', 'caption', 'activityId', 'video', 'thumbnail']);
        $this->type = MediaPostType::Reel->value;
        $this->resetPage();
        session()->flash('status', 'Your video was submitted for review.');
    }

    public function deletePost(int $postId): void
    {
        $post = MediaPost::query()
            ->where('provider_id', $this->providerId)
            ->with('assets')
            ->findOrFail($postId);

        $assets = $post->assets;
        $post->delete();

        foreach ($assets as $asset) {
            if (! Storage::disk($asset->disk)->delete($asset->path)) {
                throw new RuntimeException('The media record was removed, but its stored file could not be deleted.');
            }
        }
    }

    public function render()
    {
        $posts = MediaPost::query()
            ->where('provider_id', $this->providerId)
            ->with('assets')
            ->latest()
            ->paginate(12);

        $activities = Activity::query()
            ->where('provider_id', $this->providerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.media.provider.media-library', compact('posts', 'activities'));
    }
}
