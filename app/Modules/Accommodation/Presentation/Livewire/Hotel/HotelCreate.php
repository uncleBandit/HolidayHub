<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Hotel;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Application\Services\HotelCreator;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Application\Services\DestinationResolver;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Shared\Support\Countries;
use App\Shared\Support\Currencies;
use App\Shared\Support\Media;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Set up a hotel, then keep managing it.
 *
 * Create mode walks a host through six steps (the property, location, photos,
 * facilities and house rules, rooms and suites, review), saving a draft as it
 * goes and submitting for review at the end. Manage mode (/provider/hotel/{slug}
 * /manage) opens the same steps as tabs over an existing hotel, so a host can
 * change rates, add rooms or take one out for maintenance at any time.
 *
 * Each room type carries what guests compare: size, view, bed set-up, who can
 * stay, what the rate includes, room amenities, photos, its numbered rooms and
 * seasonal rates.
 */
#[Layout('layouts.discovery')]
class HotelCreate extends Component
{
    use WithFileUploads;

    public const STEPS = [
        1 => ['label' => 'The property', 'hint' => 'Name, stars and story'],
        2 => ['label' => 'Location', 'hint' => 'Where guests will find you'],
        3 => ['label' => 'Photos & film', 'hint' => 'Lead with film, then photos'],
        4 => ['label' => 'Facilities & rules', 'hint' => 'What’s on site, how you run it'],
        5 => ['label' => 'Rooms & suites', 'hint' => 'Qualities, inventory and rates'],
        6 => ['label' => 'Review', 'hint' => 'Check and submit'],
    ];

    public const CANCELLATION = [
        'flexible' => 'Free cancellation up to 24 hours before check-in.',
        'moderate' => 'Free cancellation up to 5 days before arrival; 50% charged after that.',
        'strict' => 'Free cancellation within 48 hours of booking if made at least 14 days ahead; non-refundable after.',
    ];

    public int $step = 1;

    public bool $managing = false;

    public ?Hotel $hotel = null;

    // The property
    public string $name = '';

    public string $slug = '';

    public int $stars = 5;

    public string $description = '';

    // Location
    public ?int $destination_id = null;

    public string $address = '';

    public string $city = '';

    public string $country = '';

    /** What all of this hotel's rates are priced in (ISO 4217). */
    public string $currency = 'KES';

    /** Once the host picks a currency, the country stops suggesting one. */
    public bool $currencyChosen = false;

    /** ISO 3166-1 alpha-2; the stored $country name follows it. */
    public string $country_code = '';

    /** What the host typed into the destination picker. */
    public string $destinationSearch = '';

    /** Shown once after a destination is picked or created. */
    public ?string $destinationNotice = null;

    public $latitude = null;

    public $longitude = null;

    // Photos
    public $cover = null;

    public ?string $cover_path = null;

    /** Optional cover film; plays where the cover photo sits. */
    public $coverVideo = null;

    public ?string $cover_video_path = null;

    /** True when the page reopened an unfinished hotel. */
    public bool $resumed = false;

    public array $gallery_paths = [];

    public array $new_gallery = [];

    /**
     * The file input binds here; each pick is appended to $new_gallery so
     * choosing more photos never drops the ones already chosen.
     */
    public array $galleryUpload = [];

    public function updatedGalleryUpload(): void
    {
        $this->validate(['galleryUpload.*' => Media::UPLOAD_RULE], ['galleryUpload.*.mimes' => 'Upload a photo (JPG, PNG, WebP) or a video (MP4, WebM, MOV).']);
        $this->new_gallery = array_values(array_merge($this->new_gallery, $this->galleryUpload));
        $this->galleryUpload = [];
        $this->autosaveMedia();
    }

    public function updatedCover(): void
    {
        $this->validateOnly('cover', ['cover' => 'image|max:6144']);
        $this->autosaveMedia();
    }

    public function updatedCoverVideo(): void
    {
        $this->validateOnly('coverVideo', ['coverVideo' => 'file|mimes:mp4,webm,mov,m4v|max:204800'], ['coverVideo.mimes' => 'The cover film must be an MP4, WebM or MOV video.']);
        $this->autosaveMedia();
    }

    public function removeCoverVideo(): void
    {
        $this->coverVideo = null;
        $this->cover_video_path = null;
        $this->autosaveMedia();
    }

    // Facilities & house rules
    public array $selectedAmenities = [];

    /**
     * Guest-facing wording for the pets and smoking choices.
     */
    public const RULE_TEXT = [
        'pets' => ['no' => 'Pets are not allowed.', 'request' => 'Pets are welcome on request.', 'yes' => 'Pets are welcome.'],
        'smoking' => ['no' => 'Non-smoking property.', 'areas' => 'Smoking in designated areas only.', 'yes' => 'Smoking is allowed.'],
    ];

    /**
     * The policies as stored when a hotel was opened for managing, so an
     * untouched rule keeps its exact wording and never counts as a change.
     */
    public array $storedPolicies = [];

    public array $policies = [
        'check_in' => '14:00',
        'check_out' => '11:00',
        'cancellation_type' => 'moderate',
        'cancellation' => '',
        'children' => 'Children of all ages are welcome.',
        'pets' => 'no',
        'smoking' => 'no',
        'deposit' => '',
    ];

    // Rooms & suites
    public array $roomTypes = [];

    public function mount(?Hotel $hotel = null): void
    {
        abort_unless(Auth::user()?->hasRole('provider') && Auth::user()->provider, 403);

        if ($hotel?->exists) {
            Gate::authorize('update', $hotel);
            $this->loadHotel($hotel);

            // A hotel still being set up reopens the wizard where it was left.
            if ($hotel->setup_step !== null) {
                $this->step = max(1, min(6, (int) $hotel->setup_step));
            } else {
                $this->managing = true;
            }

            return;
        }

        // Pick up the host's unfinished hotel rather than starting over.
        if (! request()->boolean('new') && ($draft = $this->resumableDraft())) {
            $this->loadHotel($draft);
            $this->step = max(1, min(6, (int) $draft->setup_step));
            $this->resumed = true;

            return;
        }

        $this->policies['cancellation'] = self::CANCELLATION['moderate'];
        $this->addRoomType();
    }

    /**
     * Save progress as the host goes (only while a hotel is being set up;
     * a live hotel's changes wait for "Save changes").
     */
    public function updated(string $property): void
    {
        if ($this->managing) {
            return;
        }

        $core = ['name', 'stars', 'description', 'country_code', 'destination_id', 'address', 'city', 'latitude', 'longitude', 'currency'];

        if (in_array($property, $core, true) || Str::startsWith($property, 'policies.')) {
            $this->autosaveCore();
        } elseif (Str::startsWith($property, 'selectedAmenities') && $this->hotel) {
            $this->quietly(fn () => $this->hotel->amenities()->sync(array_map('intval', $this->selectedAmenities)));
        } elseif (Str::startsWith($property, 'roomTypes')) {
            $this->autosaveRooms();
        }
    }

    // ── Steps ────────────────────────────────────────────────────────────

    public function goTo(int $step): void
    {
        if (! isset(self::STEPS[$step])) {
            return;
        }

        // While creating, steps unlock in order; managing can jump anywhere.
        if (! $this->managing && $step > $this->step) {
            return;
        }

        $this->step = $step;
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesFor($this->step), $this->messages());

        match ($this->step) {
            1, 2 => $this->saveCore(),
            3 => $this->persistMedia(),
            4 => $this->persistFacilities(),
            5 => $this->persistRooms(),
            default => null,
        };

        $this->step = min(6, $this->step + 1);
        $this->rememberStep($this->step);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function updatedName(string $value): void
    {
        if ($this->managing) {
            return;
        }

        $base = Str::slug($value) ?: 'hotel';
        $slug = $base;
        $n = 1;

        while (Hotel::where('slug', $slug)->when($this->hotel, fn ($q) => $q->whereKeyNot($this->hotel->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        $this->slug = $slug;
    }

    public function updatedPoliciesCancellationType(string $type): void
    {
        if (isset(self::CANCELLATION[$type])) {
            $this->policies['cancellation'] = self::CANCELLATION[$type];
        }
    }

    // ── Location ─────────────────────────────────────────────────────────

    public function updatedCountryCode(string $code): void
    {
        $this->country = Countries::name($code) ?? '';

        // Suggest the country's own currency until the host has chosen one.
        if (! $this->currencyChosen && ($local = Currencies::forCountry($code))) {
            $this->currency = $local;
        }

        // A destination in another country no longer applies.
        if ($this->destination_id && $this->selectedDestination?->country !== $this->country) {
            $this->destination_id = null;
            $this->destinationNotice = null;
        }
    }

    public function updatedCurrency(): void
    {
        $this->currencyChosen = true;
    }

    #[Computed]
    public function destinationResults()
    {
        return app(DestinationResolver::class)->search($this->destinationSearch, $this->country ?: null);
    }

    #[Computed]
    public function selectedDestination(): ?Destination
    {
        return $this->destination_id ? Destination::find($this->destination_id) : null;
    }

    public function selectDestination(int $id): void
    {
        $destination = Destination::findOrFail($id);

        $this->destination_id = $destination->id;
        $this->destinationSearch = '';
        $this->destinationNotice = null;
        unset($this->selectedDestination);

        if ($this->country_code === '' && ($code = Countries::codeFor($destination->country))) {
            $this->country_code = $code;
            $this->country = Countries::name($code);
        }

        if (blank($this->city)) {
            $this->city = (string) ($destination->city ?: $destination->name);
        }
    }

    /**
     * Add the place the host typed, unless it already exists, in which case
     * the existing destination is used so the catalogue has no duplicates.
     */
    public function createDestination(): void
    {
        $this->validate([
            'country_code' => ['required', Rule::in(Countries::codes())],
            'destinationSearch' => 'required|string|min:2|max:120',
        ], [
            'country_code.required' => 'Choose the country first.',
            'destinationSearch.required' => 'Type the name of the destination.',
        ]);

        [$destination, $existed] = app(DestinationResolver::class)->findOrCreate(
            $this->destinationSearch,
            $this->country,
            $this->city ?: null,
            filled($this->latitude) ? (float) $this->latitude : null,
            filled($this->longitude) ? (float) $this->longitude : null,
            Auth::user()->provider?->id,
        );

        $typed = trim($this->destinationSearch);
        $this->selectDestination($destination->id);
        $this->destinationNotice = $existed
            ? "“{$destination->name}” is already on HolidayHub, so we’ve used it rather than adding “{$typed}” again."
            : "“{$destination->name}” has been added as a new destination in {$destination->country}.";
    }

    public function clearDestination(): void
    {
        $this->destination_id = null;
        $this->destinationNotice = null;
        unset($this->selectedDestination);
    }

    // ── Photos ───────────────────────────────────────────────────────────

    public function removeGalleryPhoto(int $index): void
    {
        unset($this->gallery_paths[$index]);
        $this->gallery_paths = array_values($this->gallery_paths);

        $this->autosaveMedia();
    }

    public function removeNewGalleryPhoto(int $index): void
    {
        unset($this->new_gallery[$index]);
        $this->new_gallery = array_values($this->new_gallery);

        $this->autosaveMedia();
    }

    // ── Room types ───────────────────────────────────────────────────────

    public function addRoomType(): void
    {
        $taken = collect($this->roomTypes)->flatMap(fn ($type) => collect($type['units'])->pluck('room_number'))->all();
        $floor = count($this->roomTypes) + 1;

        $this->roomTypes[] = [
            'key' => (string) Str::uuid(),
            'id' => null,
            'name' => '',
            'description' => '',
            'price_per_night' => null,
            'size_sqm' => null,
            'view' => 'garden',
            'beds' => [['type' => 'king', 'count' => 1]],
            'max_adults' => 2,
            'max_children' => 0,
            'breakfast_included' => true,
            'refundable' => true,
            'smoking_allowed' => false,
            'accessible' => false,
            'amenities' => [],
            'gallery_paths' => [],
            'new_images' => [],
            'units' => [],
            'room_count' => 1,
            'first_room' => $floor * 100 + 1 + count(array_filter($taken, fn ($n) => intdiv((int) $n, 100) === $floor)),
            'rates' => [],
            'has_bookings' => false,
        ];

        $this->autosaveRooms();
    }

    public function duplicateRoomType(int $index): void
    {
        if (! isset($this->roomTypes[$index])) {
            return;
        }

        $copy = $this->roomTypes[$index];
        $copy['key'] = (string) Str::uuid();
        $copy['id'] = null;
        $copy['name'] = trim($copy['name'].' (copy)');
        $copy['units'] = [];
        $copy['new_images'] = [];
        $copy['has_bookings'] = false;
        $copy['first_room'] = (int) $copy['first_room'] + 100;

        array_splice($this->roomTypes, $index + 1, 0, [$copy]);

        $this->autosaveRooms();
    }

    public function removeRoomType(int $index): void
    {
        if (($this->roomTypes[$index]['has_bookings'] ?? false)) {
            $this->addError("roomTypes.$index.name", 'This room type has bookings, so it can’t be removed. Set its rooms to maintenance instead.');

            return;
        }

        unset($this->roomTypes[$index]);
        $this->roomTypes = array_values($this->roomTypes);

        $this->autosaveRooms();
    }

    public function addBed(int $index): void
    {
        $this->roomTypes[$index]['beds'][] = ['type' => 'single', 'count' => 1];

        $this->autosaveRooms();
    }

    public function removeBed(int $index, int $bed): void
    {
        unset($this->roomTypes[$index]['beds'][$bed]);
        $this->roomTypes[$index]['beds'] = array_values($this->roomTypes[$index]['beds']);

        $this->autosaveRooms();
    }

    public function addRate(int $index): void
    {
        $this->roomTypes[$index]['rates'][] = ['label' => '', 'start' => '', 'end' => '', 'rate' => null];
    }

    public function removeRate(int $index, int $rate): void
    {
        unset($this->roomTypes[$index]['rates'][$rate]);
        $this->roomTypes[$index]['rates'] = array_values($this->roomTypes[$index]['rates']);

        $this->autosaveRooms();
    }

    public function removeRoomImage(int $index, int $image): void
    {
        unset($this->roomTypes[$index]['gallery_paths'][$image]);
        $this->roomTypes[$index]['gallery_paths'] = array_values($this->roomTypes[$index]['gallery_paths']);

        $this->autosaveRooms();
    }

    // ── Save ─────────────────────────────────────────────────────────────

    public function save()
    {
        $rules = collect(range(1, 5))->flatMap(fn ($step) => $this->rulesFor($step))->all();

        try {
            $this->validate($rules, $this->messages());
            $this->validateRoomNumbers();
        } catch (ValidationException $e) {
            $this->step = $this->stepForErrors(array_keys($e->errors()));
            throw $e;
        }

        try {
            $this->saveCore();

            $hotel = app(HotelCreator::class)->saveInventory(
                $this->hotel,
                [
                    'cover' => $this->cover,
                    'cover_path' => $this->cover_path,
                    'cover_video' => $this->coverVideo,
                    'cover_video_path' => $this->cover_video_path,
                    'gallery_paths' => $this->gallery_paths,
                    'new_gallery' => array_values(array_filter($this->new_gallery)),
                ],
                array_map('intval', $this->selectedAmenities),
                $this->roomTypes
            );

            // Setup is finished: from now on the hotel opens in manage mode.
            if ($hotel->setup_step !== null) {
                $hotel->updateQuietly(['setup_step' => null]);
            }

            $accommodation = $hotel->accommodation()->firstOrFail();
            $publication = app(AccommodationPublicationService::class);
            $submitted = false;

            if (in_array($accommodation->status, [AccommodationStatus::Draft, AccommodationStatus::Rejected], true)
                && $publication->missingRequirements($accommodation) === []) {
                $publication->submitForReview($accommodation);
                $submitted = true;
            }

            session()->flash('success', match (true) {
                $submitted && $this->managing => 'Changes saved and sent for review. Your hotel goes back live once approved.',
                $submitted => 'Your hotel has been submitted for review. We’ll let you know when it’s live.',
                default => 'Changes saved.',
            });

            return $this->redirectRoute('provider.hotel-manage', $hotel, navigate: false);
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            Log::error('Hotel save failed', ['hotel_id' => $this->hotel?->id, 'error' => $e->getMessage()]);
            $this->addError('save', 'We couldn’t save your hotel. Please try again, or contact support if it keeps happening.');
        }
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.hotel.hotel-create', [
            'steps' => self::STEPS,
            'propertyAmenities' => Amenity::active()->where('type', 'general')->orderBy('name')->get(['id', 'name', 'description']),
            'roomAmenities' => Amenity::active()->where('type', 'room')->orderBy('name')->get(['id', 'name']),
            'countries' => Countries::all(),
            'currencies' => Currencies::all(),
            'symbol' => Currencies::symbol($this->currency),
            'maps' => ['key' => config('services.google.maps_key'), 'id' => config('services.google.maps_id')],
            'views' => RoomType::VIEWS,
            'bedTypes' => RoomType::BED_TYPES,
            'cancellationTypes' => self::CANCELLATION,
            'totals' => [
                'types' => count($this->roomTypes),
                'rooms' => collect($this->roomTypes)->sum(fn ($type) => (int) $type['room_count']),
                'from' => collect($this->roomTypes)->pluck('price_per_night')->filter(fn ($p) => (float) $p > 0)->min(),
            ],
            'status' => $this->hotel?->accommodation?->status,
        ])->title(($this->managing ? 'Manage ' : 'Add ').($this->name ?: 'a hotel').' · HolidayHub');
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('hotels', 'slug')->ignore($this->hotel?->id)],
                'stars' => 'required|integer|between:1,5',
                'description' => 'required|string|min:40|max:5000',
            ],
            2 => [
                'country_code' => ['required', Rule::in(Countries::codes())],
                'destination_id' => 'required|integer|exists:destinations,id',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ],
            3 => [
                'cover' => [Rule::requiredIf(blank($this->cover_path)), 'nullable', 'image', 'max:6144'],
                'coverVideo' => 'nullable|file|mimes:mp4,webm,mov,m4v|max:204800',
                'new_gallery.*' => Media::UPLOAD_RULE,
            ],
            4 => [
                'selectedAmenities' => 'required|array|min:1',
                'selectedAmenities.*' => 'integer|exists:amenities,id',
                'policies.check_in' => 'required|date_format:H:i',
                'policies.check_out' => 'required|date_format:H:i',
                'policies.cancellation' => 'required|string|max:500',
                'policies.children' => 'nullable|string|max:300',
                'policies.pets' => 'required|in:yes,no,request',
                'policies.smoking' => 'required|in:yes,no,areas',
                'policies.deposit' => 'nullable|string|max:300',
            ],
            5 => [
                'currency' => ['required', Rule::in(Currencies::codes())],
                'roomTypes' => 'required|array|min:1',
                'roomTypes.*.name' => 'required|string|max:120',
                'roomTypes.*.description' => 'nullable|string|max:2000',
                'roomTypes.*.price_per_night' => 'required|numeric|min:1',
                'roomTypes.*.size_sqm' => 'nullable|integer|min:5|max:2000',
                'roomTypes.*.view' => ['required', Rule::in(array_keys(RoomType::VIEWS))],
                'roomTypes.*.beds' => 'required|array|min:1',
                'roomTypes.*.beds.*.type' => ['required', Rule::in(array_keys(RoomType::BED_TYPES))],
                'roomTypes.*.beds.*.count' => 'required|integer|min:1|max:10',
                'roomTypes.*.max_adults' => 'required|integer|min:1|max:20',
                'roomTypes.*.max_children' => 'required|integer|min:0|max:10',
                'roomTypes.*.amenities.*' => 'integer|exists:amenities,id',
                'roomTypes.*.new_images.*' => Media::UPLOAD_RULE,
                'roomTypes.*.room_count' => 'required|integer|min:1|max:500',
                'roomTypes.*.first_room' => 'required|integer|min:1|max:99999',
                'roomTypes.*.units.*.room_number' => 'required|integer|min:1|max:99999',
                'roomTypes.*.units.*.status' => 'required|in:available,maintenance,out_of_service',
                'roomTypes.*.rates.*.label' => 'nullable|string|max:80',
                'roomTypes.*.rates.*.start' => 'required|date',
                'roomTypes.*.rates.*.end' => 'required|date|after_or_equal:roomTypes.*.rates.*.start',
                'roomTypes.*.rates.*.rate' => 'required|numeric|min:1',
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'description.min' => 'Tell guests a little more: at least 40 characters.',
            'cover.required' => 'Add a cover photo; it’s the first thing guests see.',
            'country_code.required' => 'Choose the country your hotel is in.',
            'destination_id.required' => 'Choose a destination, or add yours if it isn’t listed.',
            'latitude.required' => 'Drop a pin on your hotel’s entrance on the map.',
            'longitude.required' => 'Drop a pin on your hotel’s entrance on the map.',
            'selectedAmenities.required' => 'Choose at least one facility.',
            'roomTypes.required' => 'Add at least one room type.',
            'roomTypes.*.name.required' => 'Give this room type a name.',
            'roomTypes.*.price_per_night.required' => 'Set a nightly rate.',
            'roomTypes.*.price_per_night.min' => 'The nightly rate must be above zero.',
            'roomTypes.*.rates.*.end.after_or_equal' => 'The season must end on or after it starts.',
        ];
    }

    /**
     * Room numbers must be unique across the whole hotel.
     */
    private function validateRoomNumbers(): void
    {
        $seen = [];

        foreach ($this->roomTypes as $t => $type) {
            foreach ($type['units'] as $u => $unit) {
                $number = (int) $unit['room_number'];

                if (isset($seen[$number])) {
                    throw ValidationException::withMessages([
                        "roomTypes.$t.units.$u.room_number" => "Room {$number} is used twice.",
                    ]);
                }

                $seen[$number] = true;
            }
        }
    }

    private function stepForErrors(array $keys): int
    {
        foreach ([1 => ['name', 'slug', 'stars', 'description'], 2 => ['destination_id', 'address', 'city', 'country', 'latitude', 'longitude', 'destinationSearch'], 3 => ['cover', 'coverVideo', 'new_gallery'], 4 => ['selectedAmenities', 'policies']] as $step => $prefixes) {
            foreach ($keys as $key) {
                if (Str::startsWith($key, $prefixes)) {
                    return $step;
                }
            }
        }

        return 5;
    }

    /**
     * Create or update the hotel row itself (a draft until submitted).
     */
    private function saveCore(): void
    {
        $policies = collect($this->policies)->except('cancellation_type')->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();

        foreach (['pets', 'smoking'] as $rule) {
            $original = $this->storedPolicies[$rule] ?? null;
            $unchanged = is_string($original) && $this->choiceFor($rule, $original) === $policies[$rule];
            $policies[$rule] = $unchanged ? $original : self::RULE_TEXT[$rule][$policies[$rule]];
        }

        // Keep the stored key order and leave out empty extras, so an
        // unchanged set of rules compares equal and doesn't trigger a review.
        $policies = array_replace(array_intersect_key($policies, $this->storedPolicies), $policies);
        $policies = array_filter($policies, fn ($value, $key) => filled($value) || array_key_exists($key, $this->storedPolicies), ARRAY_FILTER_USE_BOTH);

        $this->hotel = app(HotelCreator::class)->saveDraft([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'stars' => $this->stars,
            'address' => $this->address,
            'city' => $this->city,
            'country' => Countries::name($this->country_code) ?? $this->country,
            'latitude' => filled($this->latitude) ? $this->latitude : null,
            'longitude' => filled($this->longitude) ? $this->longitude : null,
            'destination_id' => $this->destination_id,
            'policies' => $policies,
            'currency' => $this->currency,
        ], $this->hotel);
    }

    /**
     * The pets/smoking choice a stored rule stands for, whether it was saved
     * as a choice key, as our own wording or as older free text.
     */
    private function choiceFor(string $rule, string $value): string
    {
        $text = Str::lower(trim($value));

        if (isset(self::RULE_TEXT[$rule][$text])) {
            return $text;
        }

        if (($key = array_search(trim($value), self::RULE_TEXT[$rule], true)) !== false) {
            return $key;
        }

        return $rule === 'pets'
            ? match (true) {
                Str::contains($text, ['request', 'notice', 'ask']) => 'request',
                Str::startsWith($text, 'no') || Str::contains($text, ['not allowed', 'no pets']) => 'no',
                default => 'yes',
            }
            : match (true) {
                Str::contains($text, ['designated', 'area', 'outdoor', 'outside']) => 'areas',
                Str::contains($text, ['non-smoking', 'no smoking', 'not allowed']) || Str::startsWith($text, 'no') => 'no',
                default => 'yes',
            };
    }

    // ── Progress ─────────────────────────────────────────────────────────

    private function resumableDraft(): ?Hotel
    {
        return Hotel::query()
            ->where('provider_id', Auth::user()->provider->id)
            ->whereNotNull('setup_step')
            ->latest('updated_at')
            ->first();
    }

    /** Remember the furthest step reached, for resuming after a refresh. */
    private function rememberStep(int $step): void
    {
        if ($this->managing || ! $this->hotel) {
            return;
        }

        $reached = max((int) $this->hotel->setup_step, $step);

        if ((int) $this->hotel->setup_step !== $reached) {
            $this->hotel->updateQuietly(['setup_step' => $reached]);
        }
    }

    /**
     * Save what can be saved without interrupting the host; validation
     * happens when they press Continue.
     */
    private function quietly(callable $save): void
    {
        try {
            $save();
        } catch (Throwable $e) {
            Log::warning('Hotel autosave skipped', ['hotel_id' => $this->hotel?->id, 'error' => $e->getMessage()]);
        }
    }

    private function autosaveCore(): void
    {
        if (blank($this->name)) {
            return;
        }

        if (blank($this->slug)) {
            $this->updatedName($this->name);
        }

        $this->quietly(function () {
            $this->saveCore();
            $this->rememberStep($this->step);
        });
    }

    private function autosaveMedia(): void
    {
        if ($this->managing || ! $this->hotel) {
            return;
        }

        $this->quietly(fn () => $this->persistMedia());
    }

    private function autosaveRooms(): void
    {
        if ($this->managing || ! $this->hotel) {
            return;
        }

        $this->quietly(fn () => $this->persistRooms());
    }

    /** Store uploads now; they become saved paths and stop being re-sent. */
    private function persistMedia(): void
    {
        $hotel = app(HotelCreator::class)->saveMedia($this->hotel, [
            'cover' => $this->cover,
            'cover_path' => $this->cover_path,
            'cover_video' => $this->coverVideo,
            'cover_video_path' => $this->cover_video_path,
            'gallery_paths' => $this->gallery_paths,
            'new_gallery' => array_values(array_filter($this->new_gallery)),
        ]);

        $this->hotel = $hotel;
        $this->cover = null;
        $this->coverVideo = null;
        $this->new_gallery = [];
        $this->cover_path = $hotel->cover_image;
        $this->cover_video_path = $hotel->cover_video;
        $this->gallery_paths = array_values((array) ($hotel->gallery ?? []));
    }

    private function persistFacilities(): void
    {
        $this->saveCore();
        $this->hotel->amenities()->sync(array_map('intval', $this->selectedAmenities));
    }

    /**
     * Save the room types, then take back the saved ids, photo paths and
     * numbered rooms while keeping each card's key (so open cards stay open).
     */
    private function persistRooms(): void
    {
        $hotel = app(HotelCreator::class)->saveRoomTypes($this->hotel, $this->roomTypes);
        $saved = $hotel->roomTypes->sortBy('id')->values();
        $known = collect($this->roomTypes)->pluck('id')->filter()->all();
        $fresh = $saved->reject(fn (RoomType $type) => in_array($type->id, $known, true))->values();

        foreach ($this->roomTypes as $i => $type) {
            $model = $type['id'] ? $saved->firstWhere('id', $type['id']) : $fresh->shift();

            if (! $model) {
                continue;
            }

            $this->roomTypes[$i]['id'] = $model->id;
            $this->roomTypes[$i]['gallery_paths'] = array_values((array) ($model->gallery_images ?? []));
            $this->roomTypes[$i]['new_images'] = [];
            $this->roomTypes[$i]['units'] = $this->unitsOf($model);
            $this->roomTypes[$i]['room_count'] = $model->rooms->count();
        }

        $this->hotel = $hotel;
    }

    private function unitsOf(RoomType $type): array
    {
        return $type->rooms->sortBy('room_number')->map(fn ($room) => [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'status' => $room->status instanceof \BackedEnum ? $room->status->value : ($room->status ?: 'available'),
        ])->values()->all();
    }

    private function loadHotel(Hotel $hotel): void
    {
        $hotel->load(['accommodation', 'amenities', 'roomTypes' => fn ($q) => $q->orderBy('price_per_night'), 'roomTypes.rooms', 'roomTypes.amenities', 'roomTypes.seasonalRates']);

        $this->hotel = $hotel;
        $this->fill([
            'name' => (string) $hotel->name,
            'slug' => (string) $hotel->slug,
            'stars' => (int) ($hotel->stars ?: 3),
            'description' => (string) $hotel->description,
            'destination_id' => $hotel->accommodation?->destination_id,
            'address' => (string) $hotel->address,
            'city' => (string) $hotel->city,
            'country' => (string) $hotel->country,
            'country_code' => (string) Countries::codeFor($hotel->country),
            'currency' => $hotel->currency ?: 'KES',
            'currencyChosen' => true,
            'latitude' => $hotel->latitude,
            'longitude' => $hotel->longitude,
            'cover_path' => $hotel->cover_image,
            'cover_video_path' => $hotel->cover_video,
            'gallery_paths' => array_values((array) ($hotel->gallery ?? [])),
            'selectedAmenities' => $hotel->amenities->pluck('id')->map(fn ($id) => (string) $id)->all(),
        ]);

        $stored = (array) ($hotel->policies ?? []);
        $this->policies = array_merge($this->policies, array_intersect_key($stored, $this->policies));
        $this->policies['cancellation_type'] = array_search($this->policies['cancellation'], self::CANCELLATION, true) ?: 'custom';

        // Older listings stored these rules as free text; map them onto the choices.
        $this->storedPolicies = $stored;
        $this->policies['pets'] = $this->choiceFor('pets', (string) $this->policies['pets']);
        $this->policies['smoking'] = $this->choiceFor('smoking', (string) $this->policies['smoking']);

        $this->roomTypes = $hotel->roomTypes->map(fn (RoomType $type) => [
            'key' => (string) Str::uuid(),
            'id' => $type->id,
            'name' => $type->name,
            'description' => (string) $type->description,
            'price_per_night' => $type->price_per_night,
            'size_sqm' => $type->size_sqm,
            'view' => $type->view ?: 'none',
            'beds' => $type->bed_configuration ?: [['type' => 'double', 'count' => max(1, (int) $type->beds)]],
            'max_adults' => $type->max_adults ?: max(1, (int) $type->capacity),
            'max_children' => (int) $type->max_children,
            'breakfast_included' => (bool) $type->breakfast_included,
            'refundable' => (bool) $type->refundable,
            'smoking_allowed' => (bool) $type->smoking_allowed,
            'accessible' => (bool) $type->accessible,
            'amenities' => $type->amenities->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'gallery_paths' => array_values((array) ($type->gallery_images ?? [])),
            'new_images' => [],
            'units' => $type->rooms->sortBy('room_number')->map(fn ($room) => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'status' => $room->status instanceof \BackedEnum ? $room->status->value : ($room->status ?: 'available'),
            ])->values()->all(),
            'room_count' => $type->rooms->count(),
            'first_room' => $type->rooms->isEmpty() ? 101 : (int) $type->rooms->max('room_number') + 1,
            'rates' => $type->seasonalRates->sortBy('start_date')->map(fn ($rate) => [
                'label' => (string) $rate->description,
                'start' => \Carbon\Carbon::parse($rate->start_date)->format('Y-m-d'),
                'end' => \Carbon\Carbon::parse($rate->end_date)->format('Y-m-d'),
                'rate' => (float) $rate->rate,
            ])->values()->all(),
            'has_bookings' => $type->bookings()->exists(),
        ])->values()->all();

        if ($this->roomTypes === []) {
            $this->addRoomType();
        }
    }
}
