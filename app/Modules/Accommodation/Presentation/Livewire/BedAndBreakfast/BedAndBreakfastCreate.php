<?php

namespace App\Modules\Accommodation\Presentation\Livewire\BedAndBreakfast;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Application\Services\BedAndBreakfastCreator;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Presentation\Livewire\Hotel\HotelCreate;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Application\Services\DestinationResolver;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Shared\Support\Countries;
use App\Shared\Support\Currencies;
use App\Shared\Support\Media;
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
 * Set up a bed & breakfast, then keep managing it.
 *
 * Create mode walks a host through seven steps (the house, location, photos
 * and film, the walk-through, breakfast and house rules, pricing, review),
 * saving a draft as it goes and submitting for review at the end. Manage mode
 * (/provider/bed-and-breakfast/{slug}/manage) opens the same steps as tabs
 * over an existing B&B.
 *
 * A B&B is host-led, so alongside the essentials it carries the host's own
 * welcome note (kept in `policies.host_welcome`: B&Bs have no meta_data
 * column, and the note is guest-facing copy that is reviewed with the rest of
 * the listing) and what breakfast is, and when.
 */
#[Layout('layouts.discovery')]
class BedAndBreakfastCreate extends Component
{
    use WithFileUploads;

    public const STEPS = [
        1 => ['label' => 'The house', 'hint' => 'Name, rooms and your welcome'],
        2 => ['label' => 'Location', 'hint' => 'Where guests will find you'],
        3 => ['label' => 'Photos & film', 'hint' => 'Lead with film, then photos'],
        4 => ['label' => 'The walk-through', 'hint' => 'Arrival to breakfast, in reels'],
        5 => ['label' => 'Breakfast & rules', 'hint' => 'Mornings, facilities, house rules'],
        6 => ['label' => 'Pricing', 'hint' => 'Nightly rate and seasons'],
        7 => ['label' => 'Review', 'hint' => 'Check and submit'],
    ];

    private const LAST = 7;

    public int $step = 1;

    public bool $managing = false;

    public ?BedAndBreakfast $bnb = null;

    // The house
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public $rooms = 3;

    public $max_guests = 6;

    // Breakfast
    public bool $has_breakfast = true;

    public string $breakfast_details = '';

    // Location
    public ?int $destination_id = null;

    public string $address = '';

    public string $city = '';

    public string $country = '';

    /** ISO 3166-1 alpha-2; the stored $country name follows it. */
    public string $country_code = '';

    /** What the nightly rate and seasons are priced in (ISO 4217). */
    public string $currency = 'KES';

    /** Once the host picks a currency, the country stops suggesting one. */
    public bool $currencyChosen = false;

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

    /** True when the page reopened an unfinished B&B. */
    public bool $resumed = false;

    // Facilities & house rules
    public array $selectedAmenities = [];

    public array $storedPolicies = [];

    public array $policies = [
        'check_in' => '14:00',
        'check_out' => '10:30',
        'cancellation_type' => 'moderate',
        'cancellation' => '',
        'children' => 'Children of all ages are welcome.',
        'pets' => 'no',
        'smoking' => 'no',
        'deposit' => '',
        'host_welcome' => '',
    ];

    // Pricing
    public $price_per_night = null;

    /** @var array<int, array{label: string, start: string, end: string, rate: mixed}> */
    public array $seasons = [];

    /** True when the seasons were carried over from the old JSON column. */
    public bool $seasonsFromLegacy = false;

    public function mount(?BedAndBreakfast $bnb = null): void
    {
        abort_unless(Auth::user()?->hasRole('provider') && Auth::user()->provider, 403);

        if ($bnb?->exists) {
            Gate::authorize('update', $bnb);
            $this->loadBnb($bnb);

            // A B&B still being set up reopens the wizard where it was left.
            if ($bnb->setup_step !== null) {
                $this->step = max(1, min(self::LAST, (int) $bnb->setup_step));
            } else {
                $this->managing = true;
            }

            return;
        }

        $this->bnb = null;

        // Pick up the host's unfinished B&B rather than starting over.
        if (! request()->boolean('new') && ($draft = $this->resumableDraft())) {
            $this->loadBnb($draft);
            $this->step = max(1, min(self::LAST, (int) $draft->setup_step));
            $this->resumed = true;

            return;
        }

        $this->policies['cancellation'] = HotelCreate::CANCELLATION['moderate'];
    }

    /**
     * Save progress as the host goes (only while a B&B is being set up; a
     * live B&B's changes wait for "Save changes").
     */
    public function updated(string $property): void
    {
        if ($this->managing) {
            return;
        }

        $core = ['name', 'description', 'rooms', 'max_guests', 'has_breakfast', 'breakfast_details', 'country_code', 'destination_id', 'address', 'city', 'latitude', 'longitude', 'currency'];

        if (in_array($property, $core, true) || Str::startsWith($property, 'policies.')) {
            $this->autosaveCore();

            if ($property === 'currency') {
                $this->autosavePricing();
            }
        } elseif (Str::startsWith($property, 'selectedAmenities') && $this->bnb) {
            $this->quietly(fn () => $this->bnb->amenities()->sync(array_map('intval', $this->selectedAmenities)));
        } elseif ($property === 'price_per_night' || Str::startsWith($property, 'seasons')) {
            $this->autosavePricing();
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
        // The walk-through saves itself and is optional, so it has no rules.
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

        $this->step = min(self::LAST, $this->step + 1);
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

        $base = Str::slug($value) ?: 'bed-and-breakfast';
        $slug = $base;
        $n = 1;

        while (BedAndBreakfast::withTrashed()->where('slug', $slug)->when($this->bnb, fn ($q) => $q->whereKeyNot($this->bnb->id))->exists()) {
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

        // Suggest the country's own currency until the host has chosen one.
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

        $this->autosaveCore();
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

    // ── Seasons ──────────────────────────────────────────────────────────

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
        $rules = collect(range(1, self::LAST - 1))->flatMap(fn ($step) => $this->rulesFor($step))->all();

        try {
            $this->validate($rules, $this->messages());
        } catch (ValidationException $e) {
            $this->step = $this->stepForErrors(array_keys($e->errors()));
            throw $e;
        }

        try {
            $this->saveCore();
            $this->persistMedia();
            $this->bnb->amenities()->sync(array_map('intval', $this->selectedAmenities));
            $this->persistPricing();

            $bnb = $this->bnb;

            // Setup is finished: from now on the B&B opens in manage mode.
            if ($bnb->setup_step !== null) {
                $bnb->updateQuietly(['setup_step' => null]);
            }

            $accommodation = $bnb->accommodation()->firstOrFail();
            $publication = app(AccommodationPublicationService::class);
            $submitted = false;

            if (in_array($accommodation->status, [AccommodationStatus::Draft, AccommodationStatus::Rejected], true)
                && $publication->missingRequirements($accommodation) === []) {
                $publication->submitForReview($accommodation);
                $submitted = true;
            }

            session()->flash('success', match (true) {
                $submitted && $this->managing => 'Changes saved and sent for review. Your B&B goes back live once approved.',
                $submitted => 'Your B&B has been submitted for review. We’ll let you know when it’s live.',
                default => 'Changes saved.',
            });

            return $this->redirectRoute('provider.bedandbreakfast-manage', $bnb, navigate: false);
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            Log::error('B&B save failed', ['bnb_id' => $this->bnb?->id, 'error' => $e->getMessage()]);
            $this->addError('save', 'We couldn’t save your B&B. Please try again, or contact support if it keeps happening.');
        }
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function render()
    {
        $scenes = $this->bnb ? $this->bnb->scenes()->get(['id', 'media_type']) : collect();

        return view('livewire.bed-and-breakfast.bed-and-breakfast-create', [
            'steps' => self::STEPS,
            'houseAmenities' => Amenity::active()->where('type', 'general')->orderBy('name')->get(['id', 'name', 'description']),
            'roomAmenities' => Amenity::active()->where('type', 'room')->orderBy('name')->get(['id', 'name', 'description']),
            'countries' => Countries::all(),
            'currencies' => Currencies::all(),
            'symbol' => Currencies::symbol($this->currency),
            'maps' => ['key' => config('services.google.maps_key'), 'id' => config('services.google.maps_id')],
            'cancellationTypes' => HotelCreate::CANCELLATION,
            'sceneCount' => $scenes->count(),
            'sceneFilms' => $scenes->where('media_type', 'video')->count(),
            'status' => $this->bnb?->accommodation?->status,
        ])->title(($this->managing ? 'Manage ' : 'Add ').($this->name ?: 'a B&B').' · HolidayHub');
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('bed_and_breakfasts', 'slug')->ignore($this->bnb?->id)],
                'description' => 'required|string|min:40|max:5000',
                'rooms' => 'required|integer|min:1|max:50',
                'max_guests' => 'required|integer|min:1|max:100',
                'policies.host_welcome' => 'nullable|string|max:600',
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
                'has_breakfast' => 'boolean',
                'breakfast_details' => 'nullable|string|max:1000',
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
                'price_per_night' => 'required|numeric|min:1|max:99999999',
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
            'rooms.min' => 'A B&B has at least one guest room.',
            'cover.required' => 'Add a cover photo; it’s the first thing guests see.',
            'country_code.required' => 'Choose the country your B&B is in.',
            'destination_id.required' => 'Choose a destination, or add yours if it isn’t listed.',
            'latitude.required' => 'Drop a pin on your front door on the map.',
            'longitude.required' => 'Drop a pin on your front door on the map.',
            'selectedAmenities.required' => 'Choose at least one facility.',
            'price_per_night.required' => 'Set a nightly rate per room.',
            'price_per_night.min' => 'The nightly rate must be above zero.',
            'seasons.*.start.required' => 'When does this season start?',
            'seasons.*.end.required' => 'When does this season end?',
            'seasons.*.rate.required' => 'Set the nightly rate for this season.',
            'seasons.*.end.after_or_equal' => 'The season must end on or after it starts.',
        ];
    }

    private function stepForErrors(array $keys): int
    {
        $map = [
            1 => ['name', 'slug', 'description', 'rooms', 'max_guests', 'policies.host_welcome'],
            2 => ['destination_id', 'address', 'city', 'country', 'latitude', 'longitude', 'destinationSearch'],
            3 => ['cover', 'coverVideo', 'new_gallery'],
            5 => ['has_breakfast', 'breakfast_details', 'selectedAmenities', 'policies'],
        ];

        foreach ($map as $step => $prefixes) {
            foreach ($keys as $key) {
                if (Str::startsWith($key, $prefixes)) {
                    return $step;
                }
            }
        }

        return 6;
    }

    /**
     * Create or update the B&B row itself (a draft until submitted). The
     * nightly rate is saved separately, quietly, with the seasons.
     */
    private function saveCore(): void
    {
        $policies = collect($this->policies)->except('cancellation_type')->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();

        foreach (['pets', 'smoking'] as $rule) {
            $original = $this->storedPolicies[$rule] ?? null;
            $unchanged = is_string($original) && $this->choiceFor($rule, $original) === $policies[$rule];
            $policies[$rule] = $unchanged ? $original : HotelCreate::RULE_TEXT[$rule][$policies[$rule]];
        }

        // Keep the stored key order and leave out empty extras (an empty host
        // welcome, no deposit), so an unchanged set of rules compares equal
        // and doesn't trigger a review.
        $policies = array_replace(array_intersect_key($policies, $this->storedPolicies), $policies);
        $policies = array_filter($policies, fn ($value, $key) => filled($value) || array_key_exists($key, $this->storedPolicies), ARRAY_FILTER_USE_BOTH);

        $this->bnb = app(BedAndBreakfastCreator::class)->saveDraft([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rooms' => max(1, (int) $this->rooms),
            'max_guests' => max(1, (int) $this->max_guests),
            'has_breakfast' => $this->has_breakfast,
            'breakfast_details' => filled(trim($this->breakfast_details)) ? trim($this->breakfast_details) : null,
            'address' => $this->address,
            'city' => $this->city,
            'country' => Countries::name($this->country_code) ?? $this->country,
            'latitude' => filled($this->latitude) ? $this->latitude : null,
            'longitude' => filled($this->longitude) ? $this->longitude : null,
            'destination_id' => $this->destination_id,
            'policies' => $policies,
            'currency' => $this->currency,
        ], $this->bnb);
    }

    /** The pets/smoking choice a stored rule stands for (see HotelCreate). */
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

    private function resumableDraft(): ?BedAndBreakfast
    {
        return BedAndBreakfast::query()
            ->where('provider_id', Auth::user()->provider->id)
            ->whereNotNull('setup_step')
            ->latest('updated_at')
            ->first();
    }

    /** Remember the furthest step reached, for resuming after a refresh. */
    private function rememberStep(int $step): void
    {
        if ($this->managing || ! $this->bnb) {
            return;
        }

        $reached = max((int) $this->bnb->setup_step, $step);

        if ((int) $this->bnb->setup_step !== $reached) {
            $this->bnb->updateQuietly(['setup_step' => $reached]);
        }
    }

    private function quietly(callable $save): void
    {
        try {
            $save();
        } catch (Throwable $e) {
            Log::warning('B&B autosave skipped', ['bnb_id' => $this->bnb?->id, 'error' => $e->getMessage()]);
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
        if ($this->managing || ! $this->bnb) {
            return;
        }

        $this->quietly(fn () => $this->persistMedia());
    }

    private function autosavePricing(): void
    {
        if ($this->managing || ! $this->bnb) {
            return;
        }

        // Half-typed seasons are skipped by the service until they're complete.
        $this->quietly(fn () => $this->persistPricing());
    }

    /** Store uploads now; they become saved paths and stop being re-sent. */
    private function persistMedia(): void
    {
        $bnb = app(BedAndBreakfastCreator::class)->saveMedia($this->bnb, [
            'cover' => $this->cover,
            'cover_path' => $this->cover_path,
            'cover_video' => $this->coverVideo,
            'cover_video_path' => $this->cover_video_path,
            'gallery_paths' => $this->gallery_paths,
            'new_gallery' => array_values(array_filter($this->new_gallery)),
        ]);

        $this->bnb = $bnb;
        $this->cover = null;
        $this->coverVideo = null;
        $this->new_gallery = [];
        $this->cover_path = $bnb->cover_image;
        $this->cover_video_path = $bnb->cover_video;
        $this->gallery_paths = array_values((array) ($bnb->gallery ?? []));
    }

    private function persistFacilities(): void
    {
        $this->saveCore();
        $this->bnb->amenities()->sync(array_map('intval', $this->selectedAmenities));
    }

    private function persistPricing(): void
    {
        $this->bnb = app(BedAndBreakfastCreator::class)->savePricing($this->bnb, $this->price_per_night, $this->seasons);
        $this->seasonsFromLegacy = false;
    }

    private function loadBnb(BedAndBreakfast $bnb): void
    {
        $bnb->load(['accommodation', 'amenities', 'seasonalRates']);

        $this->bnb = $bnb;
        $this->fill([
            'name' => (string) $bnb->name,
            'slug' => (string) $bnb->slug,
            'description' => (string) $bnb->description,
            'rooms' => (int) ($bnb->rooms ?: 1),
            'max_guests' => (int) ($bnb->max_guests ?: 2),
            'has_breakfast' => (bool) $bnb->has_breakfast,
            'breakfast_details' => (string) $bnb->breakfast_details,
            'destination_id' => $bnb->accommodation?->destination_id,
            'address' => (string) $bnb->address,
            'city' => (string) $bnb->city,
            'country' => (string) $bnb->country,
            'country_code' => (string) Countries::codeFor($bnb->country),
            'currency' => $bnb->getCurrency(),
            'currencyChosen' => true,
            'latitude' => $bnb->latitude,
            'longitude' => $bnb->longitude,
            'cover_path' => $bnb->cover_image,
            'cover_video_path' => $bnb->cover_video,
            'gallery_paths' => array_values((array) ($bnb->gallery ?? [])),
            'selectedAmenities' => $bnb->amenities->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'price_per_night' => $bnb->price_per_night === null ? null : (float) $bnb->price_per_night,
        ]);

        $stored = (array) ($bnb->policies ?? []);
        $this->policies = array_merge($this->policies, array_intersect_key($stored, $this->policies));
        $this->policies['host_welcome'] = (string) ($stored['host_welcome'] ?? '');
        $this->policies['deposit'] = (string) ($stored['deposit'] ?? '');
        $this->policies['cancellation_type'] = array_search($this->policies['cancellation'], HotelCreate::CANCELLATION, true) ?: 'custom';

        // Older listings stored these rules as free text; map them onto the choices.
        $this->storedPolicies = $stored;
        $this->policies['pets'] = $this->choiceFor('pets', (string) $this->policies['pets']);
        $this->policies['smoking'] = $this->choiceFor('smoking', (string) $this->policies['smoking']);

        $this->seasons = $bnb->seasonalRates->sortBy('start_date')->map(fn ($rate) => [
            'label' => (string) $rate->description,
            'start' => \Carbon\Carbon::parse($rate->start_date)->format('Y-m-d'),
            'end' => \Carbon\Carbon::parse($rate->end_date)->format('Y-m-d'),
            'rate' => (float) $rate->rate,
        ])->values()->all();

        // Carry seasons over from the legacy JSON column (never written any
        // more) so the next save moves them into seasonal_rates.
        if ($this->seasons === [] && is_array($bnb->seasonal_pricing) && $bnb->seasonal_pricing !== []) {
            $this->seasons = collect($bnb->seasonal_pricing)
                ->filter(fn ($row) => is_array($row) && filled($row['start_date'] ?? $row['start'] ?? null) && filled($row['end_date'] ?? $row['end'] ?? null))
                ->map(fn ($row) => [
                    'label' => (string) ($row['label'] ?? $row['name'] ?? $row['description'] ?? ''),
                    'start' => \Carbon\Carbon::parse($row['start_date'] ?? $row['start'])->format('Y-m-d'),
                    'end' => \Carbon\Carbon::parse($row['end_date'] ?? $row['end'])->format('Y-m-d'),
                    'rate' => (float) ($row['rate'] ?? $row['price'] ?? $row['price_per_night'] ?? 0) ?: null,
                ])
                ->values()
                ->all();
            $this->seasonsFromLegacy = $this->seasons !== [];
        }
    }
}
