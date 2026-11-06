<?php

namespace App\Modules\Accommodation\Presentation\Livewire\Villa;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Application\Services\VillaCreator;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Accommodation\Presentation\Livewire\Hotel\HotelCreate;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Application\Services\DestinationResolver;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Shared\Support\Countries;
use App\Shared\Support\Currencies;
use App\Shared\Support\Media;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Set up a villa, then keep managing it.
 *
 * Create mode walks a host through seven steps (the property, location,
 * photos and film, the walk-through, facilities and rules, pricing, review),
 * saving a draft as it goes and submitting for review at the end. Manage mode
 * (/provider/villa/{slug}/manage) opens the same steps as tabs.
 *
 * The walk-through is the heart of a villa listing: an ordered reel of
 * scenes, from the gate to the view, that guests play full screen. It is
 * edited by the SceneBuilder component, which saves each change itself.
 *
 * Staff included with the villa (chef, housekeeper, butler…) are stored in
 * meta_data['staff'] rather than as amenities: they are a villa's own
 * selling point, need no catalogue rows, and changing them doesn't send a
 * live villa back to review.
 */
#[Layout('layouts.discovery')]
class VillaCreate extends Component
{
    use WithFileUploads;

    public const STEPS = [
        1 => ['label' => 'The property', 'hint' => 'Name, story and the essentials'],
        2 => ['label' => 'Location', 'hint' => 'Where guests will find you'],
        3 => ['label' => 'Photos & film', 'hint' => 'Lead with film, then photos'],
        4 => ['label' => 'The walk-through', 'hint' => 'From the gate to the view'],
        5 => ['label' => 'Facilities & rules', 'hint' => 'What’s there, how you run it'],
        6 => ['label' => 'Pricing', 'hint' => 'Nightly rate, stays and seasons'],
        7 => ['label' => 'Review', 'hint' => 'Check and submit'],
    ];

    /** Below this many scenes the walk-through feels thin; we nudge. */
    public const SUGGESTED_SCENES = 5;

    public int $step = 1;

    public bool $managing = false;

    public ?Villa $villa = null;

    /** True when the page reopened an unfinished villa. */
    public bool $resumed = false;

    // The property
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public $bedrooms = 3;

    public $bathrooms = 3;

    public $max_guests = 6;

    public $size_sqm = null;

    public bool $has_private_pool = false;

    /** Keys of Villa::STAFF included with the stay. */
    public array $staff = [];

    // Location
    public ?int $destination_id = null;

    public string $address = '';

    public string $city = '';

    public string $country = '';

    /** ISO 3166-1 alpha-2; the stored $country name follows it. */
    public string $country_code = '';

    public string $destinationSearch = '';

    public ?string $destinationNotice = null;

    public $latitude = null;

    public $longitude = null;

    // Photos & film
    public $cover = null;

    public ?string $cover_path = null;

    public $coverVideo = null;

    public ?string $cover_video_path = null;

    public array $gallery_paths = [];

    public array $new_gallery = [];

    public array $galleryUpload = [];

    // Facilities & rules
    public array $selectedAmenities = [];

    /** The policies as stored when the villa was opened, to keep wording stable. */
    public array $storedPolicies = [];

    public array $policies = [
        'check_in' => '15:00',
        'check_out' => '10:00',
        'cancellation_type' => 'moderate',
        'cancellation' => '',
        'children' => 'Children of all ages are welcome.',
        'pets' => 'no',
        'smoking' => 'no',
        'deposit' => '',
    ];

    // Pricing
    /** What all of this villa's rates are priced in (ISO 4217). */
    public string $currency = 'KES';

    public bool $currencyChosen = false;

    public $avg_price_per_night = null;

    public $min_nights = 2;

    /** @var array<int, array{label: string, start: string, end: string, rate: mixed}> */
    public array $seasons = [];

    public function mount(?Villa $villa = null): void
    {
        abort_unless(Auth::user()?->hasRole('provider') && Auth::user()->provider, 403);

        if ($villa?->exists) {
            Gate::authorize('update', $villa);
            $this->loadVilla($villa);

            if ($villa->setup_step !== null) {
                $this->step = max(1, min(7, (int) $villa->setup_step));
            } else {
                $this->managing = true;
            }

            return;
        }

        if (! request()->boolean('new') && ($draft = $this->resumableDraft())) {
            $this->loadVilla($draft);
            $this->step = max(1, min(7, (int) $draft->setup_step));
            $this->resumed = true;

            return;
        }

        $this->policies['cancellation'] = HotelCreate::CANCELLATION['moderate'];
    }

    /**
     * Save progress as the host goes (only while a villa is being set up;
     * a live villa's changes wait for "Save changes").
     */
    public function updated(string $property): void
    {
        if ($this->managing) {
            return;
        }

        $core = ['name', 'description', 'bedrooms', 'bathrooms', 'max_guests', 'size_sqm', 'has_private_pool', 'country_code', 'destination_id', 'address', 'city', 'latitude', 'longitude'];

        if (in_array($property, $core, true) || Str::startsWith($property, ['policies.', 'staff'])) {
            $this->autosaveCore();
        } elseif ($property === 'currency') {
            $this->autosaveCore();
            $this->autosavePricing();
        } elseif (Str::startsWith($property, 'selectedAmenities') && $this->villa) {
            $this->quietly(fn () => $this->villa->amenities()->sync(array_map('intval', $this->selectedAmenities)));
        } elseif (in_array($property, ['avg_price_per_night', 'min_nights'], true) || Str::startsWith($property, 'seasons')) {
            $this->autosavePricing();
        }
    }

    // ── Steps ────────────────────────────────────────────────────────────

    public function goTo(int $step): void
    {
        if (! isset(self::STEPS[$step])) {
            return;
        }

        if (! $this->managing && $step > $this->step) {
            return;
        }

        $this->step = $step;
    }

    public function nextStep(): void
    {
        // The walk-through has nothing to validate (Livewire refuses empty rules).
        if ($rules = $this->rulesFor($this->step)) {
            $this->validate($rules, $this->messages());
        }

        match ($this->step) {
            1, 2 => $this->saveCore(),
            3 => $this->persistMedia(),
            5 => $this->persistFacilities(),
            6 => $this->persistPricing(),
            default => null,
        };

        $this->step = min(7, $this->step + 1);
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

        $base = Str::slug($value) ?: 'villa';
        $slug = $base;
        $n = 1;

        while (Villa::where('slug', $slug)->when($this->villa, fn ($q) => $q->whereKeyNot($this->villa->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        $this->slug = $slug;
    }

    public function updatedPoliciesCancellationType(string $type): void
    {
        if (isset(HotelCreate::CANCELLATION[$type])) {
            $this->policies['cancellation'] = HotelCreate::CANCELLATION[$type];
        }
    }

    // ── Location ─────────────────────────────────────────────────────────

    public function updatedCountryCode(string $code): void
    {
        $this->country = Countries::name($code) ?? '';

        if (! $this->currencyChosen && ($local = Currencies::forCountry($code))) {
            $this->currency = $local;
        }

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

        if (! $this->managing) {
            $this->autosaveCore();
        }
    }

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

    // ── Photos & film ────────────────────────────────────────────────────

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

    // ── Pricing ──────────────────────────────────────────────────────────

    public function addSeason(): void
    {
        $this->seasons[] = ['label' => '', 'start' => '', 'end' => '', 'rate' => null];
    }

    public function removeSeason(int $index): void
    {
        unset($this->seasons[$index]);
        $this->seasons = array_values($this->seasons);
        $this->autosavePricing();
    }

    // ── Save ─────────────────────────────────────────────────────────────

    public function save()
    {
        $rules = collect(range(1, 6))->flatMap(fn ($step) => $this->rulesFor($step))->all();

        try {
            $this->validate($rules, $this->messages());
        } catch (ValidationException $e) {
            $this->step = $this->stepForErrors(array_keys($e->errors()));
            throw $e;
        }

        try {
            $this->saveCore();
            $this->persistMedia();
            $this->villa->amenities()->sync(array_map('intval', $this->selectedAmenities));
            $this->persistPricing();

            $villa = $this->villa;

            // Setup is finished: from now on the villa opens in manage mode.
            if ($villa->setup_step !== null) {
                $villa->updateQuietly(['setup_step' => null]);
            }

            $accommodation = $villa->accommodation()->firstOrFail();
            $publication = app(AccommodationPublicationService::class);
            $submitted = false;

            if (in_array($accommodation->status, [AccommodationStatus::Draft, AccommodationStatus::Rejected], true)
                && $publication->missingRequirements($accommodation) === []) {
                $publication->submitForReview($accommodation);
                $submitted = true;
            }

            session()->flash('success', match (true) {
                $submitted && $this->managing => 'Changes saved and sent for review. Your villa goes back live once approved.',
                $submitted => 'Your villa has been submitted for review. We’ll let you know when it’s live.',
                default => 'Changes saved.',
            });

            return $this->redirectRoute('provider.villa-manage', $villa, navigate: false);
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            Log::error('Villa save failed', ['villa_id' => $this->villa?->id, 'error' => $e->getMessage()]);
            $this->addError('save', 'We couldn’t save your villa. Please try again, or contact support if it keeps happening.');
        }
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function render()
    {
        $sceneCount = $this->villa?->exists ? $this->villa->scenes()->count() : 0;
        $sceneFilms = $this->villa?->exists ? $this->villa->scenes()->where('media_type', 'video')->count() : 0;

        return view('livewire.villa.villa-create', [
            'steps' => self::STEPS,
            // The villa's own features first, then the general comforts.
            'propertyAmenities' => Amenity::active()->whereIn('type', ['villa', 'general'])->orderByRaw("CASE WHEN type = 'villa' THEN 0 ELSE 1 END")->orderBy('name')->get(['id', 'name', 'description']),
            'staffOptions' => Villa::STAFF,
            'countries' => Countries::all(),
            'currencies' => Currencies::all(),
            'symbol' => Currencies::symbol($this->currency),
            'maps' => ['key' => config('services.google.maps_key'), 'id' => config('services.google.maps_id')],
            'cancellationTypes' => HotelCreate::CANCELLATION,
            'sceneCount' => $sceneCount,
            'sceneFilms' => $sceneFilms,
            'suggestedScenes' => self::SUGGESTED_SCENES,
            'status' => $this->villa?->accommodation?->status,
        ])->title(($this->managing ? 'Manage ' : 'Add ').($this->name ?: 'a villa').' · HolidayHub');
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('villas', 'slug')->ignore($this->villa?->id)],
                'description' => 'required|string|min:40|max:5000',
                'bedrooms' => 'required|integer|min:1|max:50',
                'bathrooms' => 'required|integer|min:1|max:50',
                'max_guests' => 'required|integer|min:1|max:100',
                'size_sqm' => 'nullable|integer|min:20|max:100000',
                'has_private_pool' => 'boolean',
                'staff' => 'array',
                'staff.*' => [Rule::in(array_keys(Villa::STAFF))],
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
            5 => [
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
            6 => [
                'currency' => ['required', Rule::in(Currencies::codes())],
                'avg_price_per_night' => 'required|numeric|min:1|max:99999999',
                'min_nights' => 'required|integer|min:1|max:90',
                'seasons.*.label' => 'nullable|string|max:80',
                'seasons.*.start' => 'required|date',
                'seasons.*.end' => 'required|date|after_or_equal:seasons.*.start',
                'seasons.*.rate' => 'required|numeric|min:1|max:99999999',
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'description.min' => 'Tell guests a little more: at least 40 characters.',
            'cover.required' => 'Add a cover photo; it’s the first thing guests see.',
            'country_code.required' => 'Choose the country your villa is in.',
            'destination_id.required' => 'Choose a destination, or add yours if it isn’t listed.',
            'latitude.required' => 'Drop a pin on your villa’s gate on the map.',
            'longitude.required' => 'Drop a pin on your villa’s gate on the map.',
            'selectedAmenities.required' => 'Choose at least one facility.',
            'avg_price_per_night.required' => 'Set a nightly rate.',
            'avg_price_per_night.min' => 'The nightly rate must be above zero.',
            'min_nights.min' => 'The minimum stay is at least one night.',
            'seasons.*.start.required' => 'When does the season start?',
            'seasons.*.end.required' => 'When does the season end?',
            'seasons.*.end.after_or_equal' => 'The season must end on or after it starts.',
            'seasons.*.rate.required' => 'Set the nightly rate for this season.',
        ];
    }

    private function stepForErrors(array $keys): int
    {
        foreach ([
            1 => ['name', 'slug', 'description', 'bedrooms', 'bathrooms', 'max_guests', 'size_sqm', 'has_private_pool', 'staff'],
            2 => ['destination_id', 'address', 'city', 'country', 'latitude', 'longitude', 'destinationSearch'],
            3 => ['cover', 'coverVideo', 'new_gallery'],
            5 => ['selectedAmenities', 'policies'],
        ] as $step => $prefixes) {
            foreach ($keys as $key) {
                if (Str::startsWith($key, $prefixes)) {
                    return $step;
                }
            }
        }

        return 6;
    }

    /**
     * Create or update the villa row itself (a draft until submitted).
     */
    private function saveCore(): void
    {
        $policies = collect($this->policies)->except('cancellation_type')->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();

        foreach (['pets', 'smoking'] as $rule) {
            $original = $this->storedPolicies[$rule] ?? null;
            $unchanged = is_string($original) && $this->choiceFor($rule, $original) === $policies[$rule];
            $policies[$rule] = $unchanged ? $original : HotelCreate::RULE_TEXT[$rule][$policies[$rule]];
        }

        // Keep the stored key order and leave out empty extras, so an
        // unchanged set of rules compares equal and doesn't trigger a review.
        $policies = array_replace(array_intersect_key($policies, $this->storedPolicies), $policies);
        $policies = array_filter($policies, fn ($value, $key) => filled($value) || array_key_exists($key, $this->storedPolicies), ARRAY_FILTER_USE_BOTH);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'bedrooms' => (int) $this->bedrooms ?: 1,
            'bathrooms' => (int) $this->bathrooms ?: 1,
            'max_guests' => (int) $this->max_guests ?: 1,
            'size_sqm' => filled($this->size_sqm) ? (int) $this->size_sqm : null,
            'has_private_pool' => $this->has_private_pool,
            'address' => $this->address,
            'city' => $this->city,
            'country' => Countries::name($this->country_code) ?? $this->country,
            'latitude' => filled($this->latitude) ? $this->latitude : null,
            'longitude' => filled($this->longitude) ? $this->longitude : null,
            'destination_id' => $this->destination_id,
            'policies' => $policies,
            'currency' => $this->currency,
        ];

        // Only write meta_data when the staff actually change.
        $staff = array_values(array_intersect(array_keys(Villa::STAFF), $this->staff));
        if (! $this->villa || $staff !== $this->villa->staffKeys()) {
            $data['meta_data'] = ['staff' => $staff];
        }

        if (! $this->villa) {
            $data['setup_step'] = $this->step;
        }

        $this->villa = app(VillaCreator::class)->saveDraft($data, $this->villa);
    }

    private function choiceFor(string $rule, string $value): string
    {
        $text = Str::lower(trim($value));

        if (isset(HotelCreate::RULE_TEXT[$rule][$text])) {
            return $text;
        }

        if (($key = array_search(trim($value), HotelCreate::RULE_TEXT[$rule], true)) !== false) {
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

    private function resumableDraft(): ?Villa
    {
        return Villa::query()
            ->where('provider_id', Auth::user()->provider->id)
            ->whereNotNull('setup_step')
            ->latest('updated_at')
            ->first();
    }

    private function rememberStep(int $step): void
    {
        if ($this->managing || ! $this->villa) {
            return;
        }

        $reached = max((int) $this->villa->setup_step, $step);

        if ((int) $this->villa->setup_step !== $reached) {
            $this->villa->updateQuietly(['setup_step' => $reached]);
        }
    }

    private function quietly(callable $save): void
    {
        try {
            $save();
        } catch (Throwable $e) {
            Log::warning('Villa autosave skipped', ['villa_id' => $this->villa?->id, 'error' => $e->getMessage()]);
        }
    }

    private function autosaveCore(): void
    {
        if ($this->managing || blank($this->name)) {
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
        if ($this->managing || ! $this->villa) {
            return;
        }

        $this->quietly(fn () => $this->persistMedia());
    }

    private function autosavePricing(): void
    {
        if ($this->managing || ! $this->villa) {
            return;
        }

        $this->quietly(fn () => $this->persistPricing());
    }

    private function persistMedia(): void
    {
        $villa = app(VillaCreator::class)->saveMedia($this->villa, [
            'cover' => $this->cover,
            'cover_path' => $this->cover_path,
            'cover_video' => $this->coverVideo,
            'cover_video_path' => $this->cover_video_path,
            'gallery_paths' => $this->gallery_paths,
            'new_gallery' => array_values(array_filter($this->new_gallery)),
        ]);

        $this->villa = $villa;
        $this->cover = null;
        $this->coverVideo = null;
        $this->new_gallery = [];
        $this->cover_path = $villa->cover_image;
        $this->cover_video_path = $villa->cover_video;
        $this->gallery_paths = array_values((array) ($villa->gallery ?? []));
    }

    private function persistFacilities(): void
    {
        $this->saveCore();
        $this->villa->amenities()->sync(array_map('intval', $this->selectedAmenities));
    }

    private function persistPricing(): void
    {
        // The currency lives on the villa row; seasons follow it.
        $this->saveCore();

        $this->villa = app(VillaCreator::class)->savePricing(
            $this->villa,
            $this->avg_price_per_night,
            (int) $this->min_nights,
            $this->seasons,
        );
    }

    private function loadVilla(Villa $villa): void
    {
        $villa->load(['accommodation', 'amenities', 'seasonalRates']);

        $this->villa = $villa;
        $this->fill([
            'name' => (string) $villa->name,
            'slug' => (string) $villa->slug,
            'description' => (string) $villa->description,
            'bedrooms' => (int) $villa->bedrooms ?: 1,
            'bathrooms' => (int) $villa->bathrooms ?: 1,
            'max_guests' => (int) $villa->max_guests ?: 1,
            'size_sqm' => $villa->size_sqm,
            'has_private_pool' => (bool) $villa->has_private_pool,
            'staff' => $villa->staffKeys(),
            'destination_id' => $villa->accommodation?->destination_id,
            'address' => (string) $villa->address,
            'city' => (string) $villa->city,
            'country' => (string) $villa->country,
            'country_code' => (string) Countries::codeFor($villa->country),
            'currency' => $villa->getCurrency(),
            'currencyChosen' => true,
            'latitude' => $villa->latitude,
            'longitude' => $villa->longitude,
            'cover_path' => $villa->cover_image,
            'cover_video_path' => $villa->cover_video,
            'gallery_paths' => array_values((array) ($villa->gallery ?? [])),
            'selectedAmenities' => $villa->amenities->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'avg_price_per_night' => $villa->avg_price_per_night ? (float) $villa->avg_price_per_night : null,
            'min_nights' => max(1, (int) $villa->min_nights),
            'seasons' => $villa->seasonalRates->sortBy('start_date')->map(fn ($rate) => [
                'label' => (string) $rate->description,
                'start' => Carbon::parse($rate->start_date)->format('Y-m-d'),
                'end' => Carbon::parse($rate->end_date)->format('Y-m-d'),
                'rate' => (float) $rate->rate,
            ])->values()->all(),
        ]);

        $stored = (array) ($villa->policies ?? []);
        $this->policies = array_merge($this->policies, array_intersect_key($stored, $this->policies));
        $this->policies['cancellation_type'] = array_search($this->policies['cancellation'], HotelCreate::CANCELLATION, true) ?: 'custom';

        $this->storedPolicies = $stored;
        $this->policies['pets'] = $this->choiceFor('pets', (string) $this->policies['pets']);
        $this->policies['smoking'] = $this->choiceFor('smoking', (string) $this->policies['smoking']);

        if (blank($this->policies['cancellation'])) {
            $this->policies['cancellation'] = HotelCreate::CANCELLATION['moderate'];
            $this->policies['cancellation_type'] = 'moderate';
        }
    }
}
