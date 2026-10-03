<?php

namespace App\Modules\Media\Presentation\Livewire;

use App\Modules\Media\Domain\Models\MediaPost;
use App\Modules\Providers\Domain\Models\Provider;
use Livewire\Component;
use Livewire\WithPagination;

class ProviderMediaProfile extends Component
{
    use WithPagination;

    public Provider $provider;

    public string $filter = 'all';

    public function mount(Provider $provider): void
    {
        abort_unless($provider->active, 404);
        $this->provider = $provider;
    }

    public function setFilter(string $filter): void
    {
        abort_unless(in_array($filter, ['all', 'reel', 'video'], true), 404);

        $this->filter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        $posts = MediaPost::query()
            ->publiclyPublished()
            ->where('provider_id', $this->provider->id)
            ->when($this->filter !== 'all', fn ($query) => $query->where('type', $this->filter))
            ->with('assets')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('livewire.media.provider-media-profile', compact('posts'));
    }
}
