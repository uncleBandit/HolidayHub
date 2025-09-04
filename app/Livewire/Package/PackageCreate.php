<?php

namespace App\Http\Livewire;

use App\Models\Package;
use Livewire\Component;
use Livewire\WithFileUploads;

class PackageCreate extends Component
{
    use WithFileUploads;

    // Form fields
    public $title, $slug, $destination, $country;
    public $short_description, $description;
    public $base_price, $discount = 0, $duration_days, $duration_nights;
    public $cover_image, $gallery = [];
    public $available_from, $available_to;

    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'duration_days' => 'required|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|image|max:2048', // 2MB
            'gallery.*' => 'nullable|image|max:2048',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after_or_equal:available_from',
        ];
    }

    public function store()
    {
        $this->validate();

        // Handle cover image upload
        $coverPath = $this->cover_image
            ? $this->cover_image->store('packages/covers', 'public')
            : null;

        // Handle gallery uploads
        $galleryPaths = [];
        if ($this->gallery) {
            foreach ($this->gallery as $image) {
                $galleryPaths[] = $image->store('packages/gallery', 'public');
            }
        }

        // Save package
        $package = Package::create([
            'title' => $this->title,
            'slug' => Str::slug($this->title),
            'destination' => $this->destination,
            'country' => $this->country,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->base_price,
            'discount_price' => $this->discount > 0
                ? $this->base_price - ($this->base_price * $this->discount / 100)
                : null,
            'currency' => 'USD', // You can make this dynamic later
            'duration_days' => $this->duration_days,
            'image_url' => $coverPath ? 'storage/' . $coverPath : null,
            'tags' => json_encode([]), // placeholder for now
            'start_date' => $this->available_from,
            'end_date' => $this->available_to,
            'status' => 'draft',
        ]);

        // Save gallery as metadata (optional: use another table for package_media)
        if ($galleryPaths) {
            $package->update([
                'gallery' => json_encode(array_map(fn($path) => 'storage/' . $path, $galleryPaths)),
            ]);
        }

        session()->flash('success', 'Package created successfully!');
        return redirect()->route('packages.index');
    }

    public function render()
    {
        return view('livewire.package.package-create');
    }
}
