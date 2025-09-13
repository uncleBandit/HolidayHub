<?php

namespace App\Livewire\Package;

use App\Models\Package;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\PackageService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PackageCreate extends Component
{
    use WithFileUploads;

    // Form fields (aligned with DB schema)
    public $name, $slug;
    public $short_description, $full_description;
    public $destination_id, $agent_id;
    public $base_price, $discount_price, $currency = 'USD';
    public $duration_days, $duration_nights;
    public $cover_image, $gallery = [];
    public $available_from, $available_to;
    public $inclusions = [];
    public $exclusions = [];
    public $itinerary = [];

    public $destinations;

    public function mount()
    {
    $this->destinations = \App\Models\Destination::all();
    }


    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'full_description' => 'nullable|string',
            'destination_id' => 'nullable|exists:destinations,id',
            'base_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:base_price',
            'currency' => 'required|in:USD,EUR,GBP,KES',
            'duration_days' => 'nullable|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'inclusions' => 'nullable|array',
            'exclusions' => 'nullable|array',
            'itinerary' => 'nullable|array',
        ];
    }

    public function validateStep($step)
    {
    $stepRules = [
        0 => ['name' => 'required|string|max:255', 'destination_id' => 'required|exists:destinations,id'],
        1 => ['short_description' => 'required|string|max:500', 'full_description' => 'required|string'],
        2 => ['base_price' => 'required|numeric|min:0', 'currency' => 'required|in:USD,EUR,GBP,KES'],
        // etc…
    ];

    $this->validate($stepRules[$step] ?? []);
    }


    public function store(PackageService $packageService)
    {
        $this->validate();

        // Auto-generate slug
        $this->slug = Str::slug($this->name);

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

        // Save package via service
        $package = $packageService->create([
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'full_description' => $this->full_description,
            'destination_id' => $this->destination_id,
            'agent_id' => Auth::id(), // logged-in agent
            'base_price' => $this->base_price,
            'discount_price' => $this->discount_price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,
            'cover_image' => $coverPath ? 'storage/' . $coverPath : null,
            'gallery' => $galleryPaths ? json_encode(array_map(fn($p) => 'storage/' . $p, $galleryPaths)) : null,
            'inclusions' => $this->inclusions ? json_encode($this->inclusions) : null,
            'exclusions' => $this->exclusions ? json_encode($this->exclusions) : null,
            'itinerary' => $this->itinerary ? json_encode($this->itinerary) : null,
            'available_from' => $this->available_from,
            'available_to' => $this->available_to,
            'status' => 'draft', // or published later
        ]);

        session()->flash('success', 'Package created successfully!');
        return redirect()->route('packages.index');
    }

    public function render()
    {
        return view('livewire.package.package-create');
    }
}
