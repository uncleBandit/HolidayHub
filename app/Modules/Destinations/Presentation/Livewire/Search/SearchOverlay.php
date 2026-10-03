<?php

namespace App\Modules\Destinations\Presentation\Livewire\Search;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Site-wide search overlay, opened from the header search button.
 */
class SearchOverlay extends Component
{
    public bool $open = false;

    public string $query = '';

    /**
     * Opened by the header button (and by the "/" shortcut).
     */
    #[On('open-search')]
    public function openSearch(): void
    {
        $this->open = true;

        $this->dispatch('focus-search');
    }

    #[On('close-search')]
    public function closeSearch(): void
    {
        $this->open = false;
    }

    /**
     * Hand the typed query over to the destinations index.
     */
    public function submitSearch(): void
    {
        $this->redirectRoute('destination.index', array_filter(['q' => trim($this->query)]));
    }

    public function render()
    {
        return view('livewire.search.search-overlay', [
            'results' => $this->results(),
        ]);
    }

    /**
     * Live matches across destinations and stays, shown while typing.
     */
    private function results(): Collection
    {
        $term = trim($this->query);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $like = '%'.$term.'%';

        $destinations = Destination::query()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('country', 'like', $like);
            })
            ->orderByDesc('popularity_score')
            ->orderByDesc('is_featured')
            ->limit(4)
            ->get()
            ->map(fn (Destination $destination) => [
                'name' => $destination->name,
                'meta' => collect([$destination->city, $destination->country])->filter()->implode(', '),
                'image' => $this->imageFor($destination),
                'url' => route('destination.show', $destination),
            ]);

        $stays = Hotel::query()
            ->where('name', 'like', $like)
            ->orderByDesc('is_featured')
            ->limit(3)
            ->get()
            ->map(fn (Hotel $hotel) => [
                'name' => $hotel->name,
                'meta' => collect([$hotel->city, $hotel->country])->filter()->implode(', '),
                'image' => $this->imageFor($hotel),
                'url' => route('hotel-show', $hotel),
            ]);

        return $destinations->concat($stays)->values();
    }

    /**
     * Cover artwork with a graceful fallback to a bundled image.
     */
    private function imageFor($item): string
    {
        $image = $item->image_url ?? $item->thumbnail ?? $item->cover_image ?? null;

        if (filled($image)) {
            return Str::startsWith($image, ['http://', 'https://', '/']) ? $image : asset('storage/'.ltrim($image, '/'));
        }

        $gallery = array_values(array_filter((array) ($item->gallery ?? [])));

        if ($gallery !== []) {
            return asset('storage/'.ltrim((string) $gallery[0], '/'));
        }

        return asset('images/welcome-screen.jpg');
    }
}
