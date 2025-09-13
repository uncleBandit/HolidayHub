<?php

namespace App\Livewire\Forms;

use App\Models\Offer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\Form;
use Livewire\Attributes\Rule;

class OfferForm extends Form
{
    use WithFileUploads;

    public ?Offer $offer = null;

    #[Rule('required|string|max:255')]
    public string $title = '';

    #[Rule('nullable|string')]
    public string $description = '';

    #[Rule('required|numeric|min:0')]
    public float $price = 0;

    #[Rule('nullable|integer|min:0|max:100')]
    public int $discount_percent = 0;

    #[Rule('nullable|image|max:4096')]
    public $main_image = null;

    public ?string $currentMainImage = null;

    #[Rule('nullable|array')]
    public array $gallery_images = [];

    #[Rule('nullable|date')]
    public ?string $start_date = null;

    #[Rule('nullable|date')]
    public ?string $end_date = null;

    #[Rule('nullable|boolean')]
    public bool $is_featured = false;

    #[Rule('nullable|boolean')]
    public bool $active = true;

    #[Rule('nullable|integer|min:1')]
    public ?int $max_capacity = null;

    #[Rule('nullable|numeric|min:0|max:5')]
    public ?float $rating = null;

    #[Rule('nullable|array')]
    public array $tags = [];

    #[Rule('nullable|integer')]
    public ?int $destination_id = null;

    #[Rule('nullable|string')]
    public ?string $offerable_type = null; // e.g., App\Models\Hotel

    #[Rule('nullable|integer')]
    public ?int $offerable_id = null;

    public function setOffer(?Offer $offer): void
    {
        $this->offer = $offer;

        if ($offer) {
            $this->title = $offer->title;
            $this->description = $offer->description;
            $this->price = $offer->price;
            $this->discount_percent = $offer->discount_percent;
            $this->currentMainImage = $offer->main_image;
            $this->start_date = $offer->start_date?->format('Y-m-d H:i:s');
            $this->end_date = $offer->end_date?->format('Y-m-d H:i:s');
            $this->is_featured = $offer->is_featured;
            $this->active = $offer->active;
            $this->max_capacity = $offer->max_capacity;
            $this->rating = $offer->rating;
            $this->tags = $offer->tags ?? [];
            $this->destination_id = $offer->destination_id;
            $this->offerable_type = $offer->offerable_type;
            $this->offerable_id = $offer->offerable_id;
        } else {
            $this->resetFields();
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'slug' => Str::slug($this->title) . '-' . uniqid(),
            'description' => $this->description,
            'price' => $this->price,
            'discount_percent' => $this->discount_percent,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_featured' => $this->is_featured,
            'active' => $this->active,
            'max_capacity' => $this->max_capacity,
            'rating' => $this->rating,
            'tags' => $this->tags,
            'destination_id' => $this->destination_id,
            'offerable_type' => $this->offerable_type,
            'offerable_id' => $this->offerable_id,
            'provider_id' => Auth::user()->provider->id,
        ];

        // Handle main image
        if ($this->main_image) {
            $path = $this->main_image->store('offers', 'public');
            $data['main_image'] = $path;

            // Delete old image if editing
            if ($this->offer && $this->offer->main_image) {
                Storage::disk('public')->delete($this->offer->main_image);
            }
        } else {
            $data['main_image'] = $this->currentMainImage;
        }

        if ($this->offer) {
            $this->offer->update($data);
        } else {
            $this->offer = Offer::create($data);
        }

        $this->resetFields();
    }

    private function resetFields(): void
    {
        $this->title = '';
        $this->description = '';
        $this->price = 0;
        $this->discount_percent = 0;
        $this->main_image = null;
        $this->currentMainImage = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->is_featured = false;
        $this->active = true;
        $this->max_capacity = null;
        $this->rating = null;
        $this->tags = [];
        $this->destination_id = null;
        $this->offerable_type = null;
        $this->offerable_id = null;
        $this->offer = null;
    }
}
