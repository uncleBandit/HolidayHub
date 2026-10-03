<?php

namespace App\Modules\Accommodation\Presentation\Livewire\BedAndBreakfast;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads; // add this at the top

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

    public ?int $destination_id = null;

    public $latitude;

    public $longitude;

    /** Media */
    public $cover_image;

    public $gallery = [];

    /** Extras */
    public bool $is_featured = false;

    public bool $is_active = true;

    public bool $is_verified = false;

    public array $policies = [
        'check_in' => '14:00',
        'check_out' => '11:00',
        'cancellation' => '',
    ];

    public array $seasonal_pricing = [];

    public array $selectedAmenities = [];

    /** Validation rules per step */
    protected function rulesForStep($step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|unique:bed_and_breakfasts,slug',
                'description' => 'required|string|min:20|max:2000',
            ],
            2 => [
                'rooms' => 'required|integer|min:1',
                'has_breakfast' => 'boolean',
                'max_guests' => 'required|integer|min:1',
                'price_per_night' => 'required|numeric|min:0.01|max:10000',
            ],
            3 => [
                'address' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'destination_id' => 'required|integer|exists:destinations,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ],
            4 => [
                'cover_image' => 'required|image|max:4096',
                'gallery.*' => 'nullable|image|max:4096',
            ],
            5 => [
                'selectedAmenities' => 'required|array|min:1',
                'selectedAmenities.*' => 'integer|exists:amenities,id',
                'policies.check_in' => 'required|string|max:20',
                'policies.check_out' => 'required|string|max:20',
                'policies.cancellation' => 'required|string|min:10|max:500',
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
        $rules = [];
        foreach (range(1, $this->totalSteps) as $step) {
            $rules = array_merge($rules, $this->rulesForStep($step));
        }
        $this->validate($rules);

        $user = Auth::user();
        $provider = $user?->provider;
        if (! $provider || ! $user->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        Log::info('Creating B&B listing.', [
            'provider_id' => $provider->id,
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

        $storedPaths = [];
        try {
            $bnb = DB::transaction(function () use ($provider, &$storedPaths): BedAndBreakfast {
                $bnb = BedAndBreakfast::create([
                'provider_id' => $provider->id,
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
                'is_featured' => false,
                'policies' => $this->policies,
                'is_active' => true,
                'is_verified' => false,
                'price_per_night' => $this->price_per_night,
                'max_guests' => $this->max_guests,
                'seasonal_pricing' => $this->seasonal_pricing,
                ]);

                $bnb->accommodation()->update(['destination_id' => $this->destination_id]);
                $coverPath = $this->cover_image->store('bnb/covers', 'public');
                if ($coverPath === false) {
                    throw new \RuntimeException('Unable to store the B&B cover image.');
                }
                $storedPaths[] = $coverPath;
                $bnb->cover_image = $coverPath;

                $galleryPaths = [];
                foreach ($this->gallery as $image) {
                    $path = $image->store('bnb/gallery', 'public');
                    if ($path === false) {
                        throw new \RuntimeException('Unable to store a B&B gallery image.');
                    }
                    $storedPaths[] = $path;
                    $galleryPaths[] = $path;
                }
                $bnb->gallery = $galleryPaths;
                $bnb->save();
                $bnb->amenities()->sync($this->selectedAmenities);

                return $bnb;
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        app(AccommodationPublicationService::class)->submitForReview($bnb->accommodation()->firstOrFail());

        session()->flash('success', 'Bed & Breakfast submitted for review.');

        return redirect()->route('bedandbreakfast.show', $bnb);

    }

    public function render()
    {
        return view('livewire.bed-and-breakfast.bed-and-breakfast-create', [
            'amenities' => Amenity::active()->get(),
            'destinations' => Destination::query()->orderBy('name')->get(['id', 'name', 'city', 'country']),
        ]);
    }
}
