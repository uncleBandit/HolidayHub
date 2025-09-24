<?php

namespace App\Livewire\BedAndBreakfast;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\BedAndBreakfast;
use App\Models\Amenity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // add this at the top


class BedAndBreakfastCreate extends Component
{
    use WithFileUploads;

    /** Step handling */
    public int $step = 1;
    public int $totalSteps = 5;

    /** Core fields */
    public $provider_id;
    public $name;
    public $slug;
    public $description;

    /** Accommodation details */
    public int $rooms = 1;
    public bool $has_breakfast = false;
    public int $max_guests = 2;
    public $price_per_night;

    /** Location */
    public $address;
    public $city;
    public $country;
    public $latitude;
    public $longitude;

    /** Media */
    public $cover_image;
    public $gallery = [];

    /** Extras */
    public bool $is_featured = false;
    public bool $is_active = true;
    public bool $is_verified = false;
    public array $policies = [];
    public array $seasonal_pricing = [];
    public array $selectedAmenities = [];

    /** Validation rules per step */
    protected function rulesForStep($step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:bed_and_breakfasts,slug',
                'description' => 'nullable|string|max:2000',
            ],
            2 => [
                'rooms' => 'required|integer|min:1',
                'has_breakfast' => 'boolean',
                'max_guests' => 'required|integer|min:1',
                'price_per_night' => 'required|numeric|min:0|max:10000',
            ],
            3 => [
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ],
            4 => [
                'cover_image' => 'nullable|image|max:4096',
                'gallery.*' => 'nullable|image|max:4096',
            ],
            5 => [
                'selectedAmenities' => 'array',
                'policies' => 'array',
                'seasonal_pricing' => 'array',
            ],
            default => [],
        };
    }

    /** Auto slug */
    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    /** Step navigation */
    public function nextStep()
    {
        $this->validate($this->rulesForStep($this->step));
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function prevStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    /** Save final record */
    public function save()
    {
        $this->validate($this->rulesForStep($this->step));

        // Log all values before saving
            Log::info('Creating BnB with values:', [
            'provider_id' => $this->provider_id ?? Auth::id(),
            'name' => $this->name,
            'slug' => Str::slug($this->name) . '-' . Str::random(8),
            'description' => $this->description,
            'rooms' => $this->rooms,
            'has_breakfast' => $this->has_breakfast,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_featured' => $this->is_featured,
            'policies' => $this->policies,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'price_per_night' => $this->price_per_night,
            'max_guests' => $this->max_guests,
            'seasonal_pricing' => $this->seasonal_pricing,
        ]);

        $bnb = BedAndBreakfast::create([
            'provider_id' => $this->provider_id ?? Auth::id(),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rooms' => $this->rooms,
            'has_breakfast' => $this->has_breakfast,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_featured' => $this->is_featured,
            'policies' => $this->policies,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'price_per_night' => $this->price_per_night,
            'max_guests' => $this->max_guests,
            'seasonal_pricing' => $this->seasonal_pricing,
        ]);

        // Media
        if ($this->cover_image) {
            $bnb->cover_image = $this->cover_image->store('bnb/covers', 'public');
        }

        if (!empty($this->gallery)) {
            $bnb->gallery = collect($this->gallery)->map(fn($img) =>
                $img->store('bnb/gallery', 'public')
            )->toArray();
        }

        $bnb->save();

        // Amenities sync
        if (!empty($this->selectedAmenities)) {
            $bnb->amenities()->sync($this->selectedAmenities);
        }

        session()->flash('success', '🎉 Bed & Breakfast created successfully!');
        return redirect()->route('bedandbreakfast.show',  $bnb);

    }

    public function render()
    {
        return view('livewire.bed-and-breakfast.bed-and-breakfast-create', [
            'amenities' => Amenity::active()->get(),
        ]);
    }
}
