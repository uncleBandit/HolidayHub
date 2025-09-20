<?php

namespace App\Livewire\Villa;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Villa;
use App\Models\Amenity;
use App\Services\Villas\VillaCreator;

class VillaCreate extends Component
{
    use WithFileUploads;

    // ==========================
    // Wizard step
    // ==========================
    public int $step = 1;

    /** Core Villa Fields */
    public $provider_id;
    public $name;
    public $slug;
    public $description;

    /** Location */
    public $address;
    public $city;
    public $country;
    public $latitude;
    public $longitude;

    /** Features */
    public $bedrooms = 1;
    public $bathrooms = 1;
    public $max_guests = 2;
    public $has_private_pool = false;
    public $is_featured = false;
    public $is_active = true;
    public $is_verified = false;

    /** Pricing */
    public $avg_price_per_night;

    /** Media */
    public $cover_image;
    public $gallery = [];

    /** Policies */
    public $policies = [
        'check_in' => '14:00',
        'check_out' => '12:00',
        'cancellation' => 'Free up to 48 hours before check-in.',
    ];

    /** Amenities */
    public $selectedAmenities = [];

    /** Draft villa */
    public ?Villa $draftVilla = null;

    // ==========================
    // Step Validation Rules
    // ==========================
    protected function stepRules(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => [
                    'required',
                    'string',
                    Rule::unique('villas', 'slug')->ignore($this->draftVilla?->id),
                ],
                'description' => 'nullable|string',
            ],
            2 => [
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ],
            3 => [
                'bedrooms' => 'required|integer|min:1',
                'bathrooms' => 'required|integer|min:1',
                'max_guests' => 'required|integer|min:1',
                'avg_price_per_night' => 'required|numeric|min:0',
            ],
            4 => [
                'cover_image' => 'nullable|image|max:4096',
                'gallery.*' => 'nullable|image|max:4096',
            ],
            5 => [
                'selectedAmenities' => 'array',
                'policies.check_in' => 'required|string',
                'policies.check_out' => 'required|string',
                'policies.cancellation' => 'nullable|string|max:500',
            ],
            default => [],
        };
    }

    // ==========================
    // Slug auto-generation
    // ==========================
    public function updatedName($value)
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $count = 1;

        while (Villa::where('slug', $slug)->when(
            $this->draftVilla,
            fn($q) => $q->where('id', '!=', $this->draftVilla->id)
        )->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        $this->slug = $slug;
    }

    // ==========================
    // Wizard Navigation
    // ==========================
    public function nextStep()
    {
        $this->validate($this->stepRules($this->step));
        $this->saveDraft();
        $this->step++;
    }

    public function previousStep()
    {
        $this->step = max(1, $this->step - 1);
    }

    // ==========================
    // Draft saving
    // ==========================
    protected function saveDraft()
    {
        $this->draftVilla = app(VillaCreator::class)->saveDraft(
            $this->only([
                'provider_id', 'name', 'slug', 'description',
                'address', 'city', 'country', 'latitude', 'longitude',
                'bedrooms', 'bathrooms', 'max_guests', 'has_private_pool', 'is_featured',
                'is_active', 'is_verified', 'avg_price_per_night', 'policies',
            ]),
            $this->draftVilla
        );
    }

    // ==========================
    // Final Save
    // ==========================
    public function save()
    {
        $this->validate($this->stepRules($this->step));

        $villa = app(VillaCreator::class)->createVilla(
            $this->draftVilla,
            $this->cover_image,
            $this->gallery,
            $this->selectedAmenities
        );

        session()->flash('success', 'Villa created successfully!');
        return redirect()->route('villas.show', $villa->slug);
    }

    // ==========================
    // Render
    // ==========================
    public function render()
    {
        return view('livewire.villa.villa-create', [
            'amenities' => Amenity::active()->get(),
            'step' => $this->step,
        ]);
    }
}
