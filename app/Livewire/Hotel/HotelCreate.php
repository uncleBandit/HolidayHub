<?php

namespace App\Livewire\Hotel;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hotel;
use App\Models\Amenity;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\Hotels\HotelCreator;

class HotelCreate extends Component
{
    use WithFileUploads;

    // Wizard step
    public int $step = 1;

    /** Core hotel fields */
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
    public $stars = 3;
    public $is_featured = false;
    public $is_active = true;
    public $is_verified = false;

    /** Pricing */
    public $avg_price_per_night;
    public $seasonal_rates = [];

    /** Media */
    public $cover_image;
    public $gallery = [];

    /** Policies */
    public $policies = [
        'check_in' => '14:00',
        'check_out' => '12:00',
        'cancellation' => 'Free up to 48 hours before check-in.',
    ];

    /** Hotel amenities */
    public $selectedAmenities = [];

    /** Draft hotel */
    public ?Hotel $draftHotel = null;

    /** Dynamic RoomTypes */
    public $roomTypes = [];
    protected $roomTypeDefaults = [
        'name' => '',
        'slug' => '',
        'description' => '',
        'price_per_night' => 0,
        'capacity' => 2,
        'beds' => 1,
        'gallery_images' => [],
        'amenities' => [],
    ];

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
                    Rule::unique('hotels', 'slug')->ignore($this->draftHotel?->id),
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
                'stars' => 'required|integer|min:1|max:5',
                'avg_price_per_night' => 'required|numeric|min:0',
                'seasonal_rates' => 'array',
            ],
            4 => [
                'cover_image' => 'nullable|image|max:4096',
                'gallery.*' => 'nullable|image|max:4096',
            ],
            5 => [
                'policies.check_in' => 'required|string',
                'policies.check_out' => 'required|string',
                'policies.cancellation' => 'nullable|string|max:500',
                'selectedAmenities' => 'array',
            ],
            6 => $this->roomTypeRules(),
            default => [],
        };
    }

    // ==========================
    // Dynamic RoomType Rules
    // ==========================
    protected function roomTypeRules(): array
    {
        $rules = [];
        foreach ($this->roomTypes as $index => $room) {
            $rules["roomTypes.$index.name"] = 'required|string|max:255';
            $rules["roomTypes.$index.slug"] = 'required|string|unique:room_types,slug';
            $rules["roomTypes.$index.price_per_night"] = 'required|numeric|min:0';
            $rules["roomTypes.$index.capacity"] = 'required|integer|min:1';
            $rules["roomTypes.$index.beds"] = 'required|integer|min:1';
            $rules["roomTypes.$index.gallery_images.*"] = 'nullable|image|max:4096';
        }
        return $rules;
    }

    // ==========================
    // Slug auto-generation
    // ==========================
    public function updatedName($value)
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $count = 1;

        while (Hotel::where('slug', $slug)->when(
            $this->draftHotel,
            fn($q) => $q->where('id', '!=', $this->draftHotel->id)
        )->exists()) {
            $slug = "{$baseSlug}-{$count}";
            $count++;
        }

        $this->slug = $slug;
    }

    public function updatedRoomTypesName($value, $index)
    {
        $this->roomTypes[$index]['slug'] = Str::slug($value);
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
        $this->draftHotel = app(HotelCreator::class)->saveDraft(
            $this->only([
                'provider_id','name','slug','description',
                'address','city','country','latitude','longitude',
                'stars','is_featured','is_active','is_verified',
                'avg_price_per_night','policies',
            ]),
            $this->draftHotel
        );
    }

    // ==========================
    // Manage RoomTypes
    // ==========================
    public function addRoomType()
    {
        $this->roomTypes[] = $this->roomTypeDefaults;
    }

    public function removeRoomType($index)
    {
        unset($this->roomTypes[$index]);
        $this->roomTypes = array_values($this->roomTypes);
    }

    // ==========================
    // Final Save
    // ==========================
    public function save()
    {
        $this->validate($this->stepRules($this->step));

        $hotel = app(HotelCreator::class)->createHotelWithRoomTypes(
            $this->draftHotel,
            $this->cover_image,
            $this->gallery,
            $this->selectedAmenities,
            $this->roomTypes
        );

        session()->flash('success', 'Hotel created successfully!');
        return redirect()->route('hotels.show', $hotel->slug);
    }

    // ==========================
    // Render
    // ==========================
    public function render()
    {
        return view('livewire.hotel.hotel-create', [
            'amenities' => Amenity::active()->get(),
            'step' => $this->step,
        ]);
    }
}
