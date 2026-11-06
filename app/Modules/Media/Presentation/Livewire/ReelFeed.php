<?php

namespace App\Modules\Media\Presentation\Livewire;

use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Media\Domain\Enums\MediaPostType;
use App\Modules\Media\Domain\Models\MediaPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReelFeed extends Component
{
    private const PAGE_SIZE = 6;

    #[Locked]
    public array $postIds = [];

    #[Locked]
    public ?int $cursor = null;

    #[Locked]
    public bool $hasMore = true;

    public function mount(): void
    {
        $this->loadMore();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $query = $this->feedQuery();

        if ($this->cursor !== null) {
            $query->where('id', '<', $this->cursor);
        }

        $page = $query->orderByDesc('id')->limit(self::PAGE_SIZE)->get();
        $this->postIds = array_values(array_unique([...$this->postIds, ...$page->modelKeys()]));
        $this->cursor = $page->last()?->id;
        $this->hasMore = $page->count() === self::PAGE_SIZE;
    }

    private function feedQuery(): Builder
    {
        return MediaPost::query()
            ->publiclyPublished()
            ->where('type', MediaPostType::Reel->value);
    }

    public function render()
    {
        $posts = empty($this->postIds)
            ? collect()
            : $this->feedQuery()
                ->whereIn('id', $this->postIds)
                ->with(['provider', 'assets', 'targetable' => fn (MorphTo $morph) => $morph->morphWith([Accommodation::class => ['bookable']])])
                ->orderByDesc('id')
                ->get();

        // The full-screen feed renders under the dark reels chrome of the layout.
        return view('livewire.media.reel-feed', compact('posts'))->layoutData(['reels' => true]);
    }
}
