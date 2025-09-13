<?php

namespace App\Livewire\Gallery;

use Livewire\Component;

class GalleryComponent extends Component
{
    /** @var array<int, array<string, string>> */
    public array $images = [];

    /** The index of the currently selected image */
    public int $activeIndex = 0;

    /** Whether the gallery modal is open */
    public bool $isOpen = false;

    public function mount(array $images = []): void
    {
        $this->setInitialState($images);
    }

    /**
     * Initializes the component's state, setting up images and the active index.
     *
     * @param array<string|array<string, string>> $images
     */
    protected function setInitialState(array $images): void
    {
        // Normalize input to ensure it's always an array of ['url' => '...']
        $this->images = collect($images)->map(function ($image) {
            return is_array($image) ? $image : ['url' => $image];
        })->toArray();

        // If no images are provided, use a placeholder
        if (empty($this->images)) {
            $this->images = [
                ['url' => 'https://via.placeholder.com/1200x800?text=No+Image+Available']
            ];
        }

        // Always set the active index to a safe, valid value (e.g., 0)
        $this->activeIndex = 0;
    }

    public function open(int $index = 0): void
    {
        $this->setActive($index);
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->dispatch('galleryClosed');
    }

    public function setActive(int $index): void
    {
        if (isset($this->images[$index])) {
            $this->activeIndex = $index;
            $this->dispatch('imageChanged', index: $index);
        }
    }

    public function next(): void
    {
        $this->setActive(($this->activeIndex + 1) % count($this->images));
    }

    public function prev(): void
    {
        $this->setActive(($this->activeIndex - 1 + count($this->images)) % count($this->images));
    }

    public function render()
    {
        return view('livewire.gallery.gallery-component');
    }
}
