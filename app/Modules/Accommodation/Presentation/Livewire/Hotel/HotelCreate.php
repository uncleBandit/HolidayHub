<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Hotel;

use App\Modules\Accommodation\Application\Services\HotelCreator;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Catalog\Domain\Models\Amenity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

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
    protected function rules(int $step): array
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
            $rules["roomTypes.$index.name"] = 'nullable|string|max:255';
            $rules["roomTypes.$index.slug"] = 'nullable|string|unique:room_types,slug';
            $rules["roomTypes.$index.price_per_night"] = 'nullable|numeric|min:0';
            $rules["roomTypes.$index.capacity"] = 'nullable|integer|min:1';
            $rules["roomTypes.$index.beds"] = 'nullable|integer|min:1';
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
            fn ($q) => $q->where('id', '!=', $this->draftHotel->id)
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
        $rules = $this->rules($this->step);

        if (! empty($rules)) {
            $this->validate($rules);
        }

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
        try {
            $providerId = Auth::user()?->provider?->id;

            $data = $this->only([
                'name',
                'slug',
                'description',
                'address',
                'city',
                'country',
                'latitude',
                'longitude',
                'stars',
                'is_featured',
                'is_active',
                'is_verified',
                'avg_price_per_night',
                'policies',
                'cover_image',   // ✅ include from migration
                'gallery',       // ✅ include from migration
            ]);

            // Ensure provider_id is always set
            $data['provider_id'] = $providerId ?? auth()->id();

            // Add safe defaults if missing
            $data['stars'] = $data['stars'] ?? 3;
            $data['is_featured'] = $data['is_featured'] ?? false;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_verified'] = $data['is_verified'] ?? false;
            $data['avg_price_per_night'] = $data['avg_price_per_night'] ?? 0.00;

            Log::info('Saving hotel draft', [
                'providerId' => $data['provider_id'],
                'draftHotelId' => $this->draftHotel?->id,
                'slug' => $data['slug'] ?? null,
            ]);

            $this->draftHotel = app(HotelCreator::class)->saveDraft($data, $this->draftHotel);

            Log::info('Hotel draft saved successfully', [
                'hotelId' => $this->draftHotel->id,
                'slug' => $this->draftHotel->slug,
                'is_active' => $this->draftHotel->is_active,
                'is_verified' => $this->draftHotel->is_verified,
            ]);
        } catch (Throwable $e) {
            Log::error('Hotel draft save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'draftHotelId' => $this->draftHotel?->id,
                'providerId' => Auth::user()?->provider?->id,
            ]);

            session()->flash('error', 'Failed to save draft. Please try again later.');
            throw $e; // rethrow if you want Livewire to catch
        }
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
        try {
            Log::info('Hotel save initiated', [
                'step' => $this->step,
                'draftHotelId' => $this->draftHotel?->id,
                'providerId' => auth()->id(),
            ]);

            // ✅ Always use rules(), never the default rules()
            $rules = $this->rules($this->step);
            $this->validate($rules);

            Log::info('Validation passed', [
                'step' => $this->step,
                'rules_used' => array_keys($rules),
            ]);

            // Try creating hotel with room types
            $hotel = app(HotelCreator::class)->createHotelWithRoomTypes(
                $this->draftHotel,
                $this->cover_image,
                $this->gallery,
                $this->selectedAmenities,
                $this->roomTypes
            );

            Log::info('Hotel created successfully', [
                'hotelId' => $hotel->id,
                'slug' => $hotel->slug,
                'providerId' => $hotel->provider_id,
                'roomTypesCount' => $hotel->roomTypes()->count(),
            ]);

            session()->flash('success', 'Hotel created successfully!');

            return redirect()->route('hotel-show', $hotel->slug);

        } catch (Throwable $e) {
            Log::error('Hotel save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'draftHotelId' => $this->draftHotel?->id,
                'providerId' => auth()->id(),
                'step' => $this->step,
            ]);

            session()->flash('error', 'Failed to save hotel. Please try again or contact support.');

            return back()->withInput();
        }
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
