<?php

namespace App\Modules\Activities\Presentation\Livewire\Activity;

use App\Modules\Accommodation\Presentation\Livewire\Hotel\HotelCreate;
use App\Modules\Activities\Application\Services\ActivityCreator;
use App\Modules\Activities\Application\Services\ActivityPublicationService;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Destinations\Application\Services\DestinationResolver;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Support\Countries;
use App\Support\Currencies;
use App\Support\Media;
use DateTimeZone;
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
 * Set up an experience (a tour, safari, class or adventure), then keep
 * managing it.
 *
 * Create mode walks a host through eight steps, saving a draft as it goes
 * and submitting for review at the end. Manage mode
 * (/provider/experience/{slug}/manage) opens the same steps as tabs.
 *
 * An experience sells a feeling, so it leads with film: a cover film, a
 * gallery of photos and films, and above all "Moment by moment", a reel walk
 * of the experience from the meeting point to the last light, edited by the
 * SceneBuilder (which saves each change itself).
 *
 * Where things live: the hook is short_description; the meeting point is an
 * ActivityLocation (meeting_point); the plan is itinerary items; what to
 * bring, the fitness level and must-haves are requirements (bring, fitness,
 * requirement); the cancellation policy and the booking window are in
 * `attributes`; the weekly timetable is one ActivitySchedule per weekday and
 * start time, whose sessions are generated ahead for guests to book.
 */
#[Layout('layouts.discovery')]
class ActivityCreate extends Component
{
    use WithFileUploads;

    public const STEPS = [
        1 => ['label' => 'The experience', 'hint' => 'Name, hook and story'],
        2 => ['label' => 'Where it happens', 'hint' => 'Destination and meeting point'],
        3 => ['label' => 'Photos & film', 'hint' => 'Lead with film, then photos'],
        4 => ['label' => 'Moment by moment', 'hint' => 'Let guests live it first'],
        5 => ['label' => 'The plan', 'hint' => 'How it unfolds, what’s included'],
        6 => ['label' => 'Who it’s for', 'hint' => 'Group, ages, safety, policy'],
        7 => ['label' => 'Times & price', 'hint' => 'When it runs, what it costs'],
        8 => ['label' => 'Review', 'hint' => 'Check and submit'],
    ];

    /** Below this many moments the reel walk feels thin; we nudge. */
    public const SUGGESTED_SCENES = 5;

    /** The languages guests can filter by (codes match the guest page). */
    public const LANGUAGES = [
        'en' => 'English', 'sw' => 'Swahili', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish',
        'it' => 'Italian', 'pt' => 'Portuguese', 'ar' => 'Arabic', 'zh' => 'Chinese', 'ja' => 'Japanese',
    ];

    public const FITNESS = [
        'easy' => ['Easy', 'A gentle pace that suits most guests.'],
        'moderate' => ['Moderate', 'Some walking or effort; a reasonable level of fitness helps.'],
        'challenging' => ['Challenging', 'Sustained effort; guests should be fit and active.'],
    ];

    /** Stored as their labels in activities.accessibility. */
    public const ACCESSIBILITY = [
        'wheelchair' => 'Wheelchair accessible',
        'step_free' => 'Step-free access',
        'limited_mobility' => 'Suitable for limited mobility',
        'strollers' => 'Pushchairs and prams welcome',
        'service_animals' => 'Service animals welcome',
        'transport' => 'Accessible transport available',
        'hearing' => 'Support for guests with hearing loss',
        'visual' => 'Support for guests with low vision',
    ];

    public const CUTOFFS = ['' => 'Right up to the start', '60' => '1 hour before', '180' => '3 hours before', '720' => '12 hours before', '1440' => '24 hours before', '2880' => '48 hours before'];

    public const NOTICE = ['0' => 'No notice needed', '60' => '1 hour', '180' => '3 hours', '720' => '12 hours', '1440' => '1 day', '2880' => '2 days'];

    public const HORIZONS = [4 => '4 weeks', 8 => '8 weeks', 12 => '3 months', 26 => '6 months'];

    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

    private const LISTS = ['highlights', 'inclusions', 'exclusions', 'bring', 'requirements'];

    public int $step = 1;

    public bool $managing = false;

    public ?Activity $activity = null;

    /** True when the page reopened an unfinished experience. */
    public bool $resumed = false;

    // The experience
    public string $name = '';

    public string $slug = '';

    public ?int $category_id = null;

    public string $short_description = '';

    public string $description = '';

    public array $highlights = ['', '', ''];

    public array $languages = ['en'];

    // Where it happens
    public ?int $destination_id = null;

    public string $country = '';

    /** ISO 3166-1 alpha-2; the stored $country name follows it. */
    public string $country_code = '';

    public string $destinationSearch = '';

    public ?string $destinationNotice = null;

    public $latitude = null;

    public $longitude = null;

    public string $city = '';

    public string $meeting_point = '';

    public string $address = '';

    public string $meeting_instructions = '';

    // Photos & film
    public $cover = null;

    public ?string $cover_path = null;

    public $coverVideo = null;

    public ?string $cover_video_path = null;

    public array $gallery_paths = [];

    public array $new_gallery = [];

    public array $galleryUpload = [];

    // The plan
    /** @var array<int, array{title: string, description: string, minutes: mixed}> */
    public array $itinerary = [];

    public $duration_hours = 3;

    public $duration_mins = 0;

    public array $inclusions = [''];

    public array $exclusions = [''];

    public array $bring = [''];

    // Who it's for
    public $capacity = 10;

    public $min_age = null;

    public $max_age = null;

    public array $accessibility = [];

    public string $fitness = 'easy';

    /** Must-haves before booking ("Able to swim", "Valid passport"). */
    public array $requirements = [''];

    public bool $weather_dependent = false;

    public string $weather_cancellation_policy = '';

    public string $safety_instructions = '';

    public string $cancellation_type = 'moderate';

    public string $cancellation = '';

    // Times & price
    /** What this experience is priced in (ISO 4217). */
    public string $currency = 'KES';

    public bool $currencyChosen = false;

    public $base_price = null;

    public string $timezone = 'Africa/Nairobi';

    public bool $timezoneChosen = false;

    /** @var array<int, array{days: array<int, string>, start: string, end: string, capacity: mixed}> */
    public array $schedules = [];

    public $booking_cutoff_minutes = '720';

    public $minimum_notice_minutes = '720';

    public $horizon_weeks = 12;

    /**
     * Defaults the form filled in for a listing that had none (no languages,
     * fitness level or cancellation policy). Left untouched they aren't
     * saved, so opening and saving a live experience never changes it.
     */
    public array $prefilled = [];

    /** Shown after the schedule is saved. */
    public ?string $scheduleNotice = null;

    public function mount(?Activity $activity = null): void
    {
        abort_unless(Auth::user()?->hasRole('provider') && Auth::user()->provider, 403);

        if ($activity?->exists) {
            Gate::authorize('update', $activity);
            $this->loadActivity($activity);

            if ($activity->setup_step !== null) {
                $this->step = max(1, min(8, (int) $activity->setup_step));
            } else {
                $this->managing = true;
            }

            return;
        }

        if (! request()->boolean('new') && ($draft = $this->resumableDraft())) {
            $this->loadActivity($draft);
            $this->step = max(1, min(8, (int) $draft->setup_step));
            $this->resumed = true;

            return;
        }

        $this->cancellation = HotelCreate::CANCELLATION['moderate'];
        $this->schedules = [['days' => [], 'start' => '09:00', 'end' => '12:00', 'capacity' => 10]];
    }

    /**
     * Save progress as the host goes (only while an experience is being set
     * up; a live experience's changes wait for "Save changes").
     */
    public function updated(string $property): void
    {
        if ($this->managing) {
            return;
        }

        $core = ['name', 'category_id', 'short_description', 'description', 'country_code', 'destination_id', 'city', 'duration_hours', 'duration_mins', 'capacity', 'min_age', 'max_age', 'weather_dependent', 'weather_cancellation_policy', 'safety_instructions', 'cancellation', 'cancellation_type'];

        if (in_array($property, $core, true) || Str::startsWith($property, ['highlights', 'inclusions', 'exclusions', 'accessibility'])) {
            $this->autosaveCore();
        } elseif (in_array($property, ['meeting_point', 'address', 'meeting_instructions', 'latitude', 'longitude', 'fitness'], true) || Str::startsWith($property, ['languages', 'itinerary', 'bring', 'requirements'])) {
            $this->autosave(fn () => $this->persistDetails());
        } elseif (in_array($property, ['currency', 'base_price', 'timezone', 'booking_cutoff_minutes', 'minimum_notice_minutes'], true)) {
            $this->autosave(fn () => $this->persistPricing());
        } elseif ($property === 'horizon_weeks' || Str::startsWith($property, 'schedules')) {
            $this->autosave(fn () => $this->persistSchedule());
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
        // Moment by moment has nothing to validate (Livewire refuses empty rules).
        if ($rules = $this->rulesFor($this->step)) {
            $this->validate($rules, $this->messages());
        }

        match ($this->step) {
            1 => $this->persistCore(withDetails: true),
            2 => $this->persistCore(withDetails: true),
            3 => $this->persistMedia(),
            5, 6 => $this->persistCore(withDetails: true),
            7 => $this->persistTimes(),
            default => null,
        };

        $this->step = min(8, $this->step + 1);
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

        $base = Str::slug($value) ?: 'experience';
        $slug = $base;
        $n = 1;

        while (Activity::withTrashed()->where('slug', $slug)->when($this->activity, fn ($q) => $q->whereKeyNot($this->activity->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        $this->slug = $slug;
    }

    public function updatedCancellationType(string $type): void
    {
        if (isset(HotelCreate::CANCELLATION[$type])) {
            $this->cancellation = HotelCreate::CANCELLATION[$type];
            $this->autosaveCore();
        }
    }

    public function updatedWeatherDependent(bool $on): void
    {
        if ($on && blank($this->weather_cancellation_policy)) {
            $this->weather_cancellation_policy = 'If the weather makes it unsafe, we’ll offer you another date or a full refund.';
        }
    }

    // ── Lists ────────────────────────────────────────────────────────────

    public function addLine(string $list): void
    {
        if (in_array($list, self::LISTS, true) && count($this->{$list}) < 12) {
            $this->{$list}[] = '';
        }
    }

    public function removeLine(string $list, int $index): void
    {
        if (! in_array($list, self::LISTS, true)) {
            return;
        }

        unset($this->{$list}[$index]);
        $this->{$list} = array_values($this->{$list});
        $this->updated($list);
    }

    public function addStop(): void
    {
        if (count($this->itinerary) < 20) {
            $this->itinerary[] = ['title' => '', 'description' => '', 'minutes' => null];
        }
    }

    public function removeStop(int $index): void
    {
        unset($this->itinerary[$index]);
        $this->itinerary = array_values($this->itinerary);
        $this->updated('itinerary');
    }

    public function moveStop(int $index, int $direction): void
    {
        $to = $index + ($direction < 0 ? -1 : 1);
        if (! isset($this->itinerary[$index], $this->itinerary[$to])) {
            return;
        }

        [$this->itinerary[$index], $this->itinerary[$to]] = [$this->itinerary[$to], $this->itinerary[$index]];
        $this->updated('itinerary');
    }

    /** Set the total duration to the sum of the timeline's stops. */
    public function useTimelineTotal(): void
    {
        $total = $this->timelineMinutes();
        if ($total > 0) {
            $this->duration_hours = intdiv($total, 60);
            $this->duration_mins = $total % 60;
            $this->updated('duration_hours');
        }
    }

    // ── Location ─────────────────────────────────────────────────────────

    public function updatedCountryCode(string $code): void
    {
        $this->country = Countries::name($code) ?? '';

        if (! $this->currencyChosen && ($local = Currencies::forCountry($code))) {
            $this->currency = $local;
        }

        if (! $this->timezoneChosen && ($zone = $this->zoneFor($code))) {
            $this->timezone = $zone;
        }

        if ($this->destination_id && $this->selectedDestination?->country !== $this->country) {
            $this->destination_id = null;
            $this->destinationNotice = null;
        }

        $this->autosaveCore();
    }

    public function updatedCurrency(): void
    {
        $this->currencyChosen = true;
    }

    public function updatedTimezone(): void
    {
        $this->timezoneChosen = true;
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
            $this->updatedCountryCode($code);
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

    // ── Times ────────────────────────────────────────────────────────────

    public function addSlot(): void
    {
        $last = end($this->schedules) ?: null;
        $this->schedules[] = [
            'days' => [],
            'start' => '',
            'end' => '',
            'capacity' => $last['capacity'] ?? ((int) $this->capacity ?: 10),
        ];
    }

    public function removeSlot(int $index): void
    {
        unset($this->schedules[$index]);
        $this->schedules = array_values($this->schedules);
        $this->updated('schedules');
    }

    /** A new start time suggests an end time from the experience's length. */
    public function updatingSchedules($value, ?string $key = null): void
    {
        [$row, $field] = array_pad(explode('.', (string) $key), 2, null);

        if ($field === 'start' && preg_match('/^\d{2}:\d{2}$/', (string) $value) && isset($this->schedules[$row]) && blank($this->schedules[$row]['end'])) {
            $minutes = max(30, min($this->durationMinutes(), 1439));
            $this->schedules[$row]['end'] = now()->setTimeFromTimeString($value)->addMinutes($minutes)->format('H:i');
        }
    }

    // ── Save ─────────────────────────────────────────────────────────────

    public function save()
    {
        $rules = collect(range(1, 7))->flatMap(fn ($step) => $this->rulesFor($step))->all();

        try {
            $this->validate($rules, $this->messages());
        } catch (ValidationException $e) {
            $this->step = $this->stepForErrors(array_keys($e->errors()));
            throw $e;
        }

        try {
            $this->persistCore(withDetails: true);
            $this->persistMedia();
            $this->persistTimes();

            $activity = $this->activity;

            // Setup is finished: from now on the experience opens in manage mode.
            if ($activity->setup_step !== null) {
                $activity->updateQuietly(['setup_step' => null]);
            }

            $submitted = false;
            if (in_array($activity->status, [ActivityStatus::Draft, ActivityStatus::Rejected], true)) {
                app(ActivityPublicationService::class)->submit($activity);
                $submitted = true;
            }

            session()->flash('success', match (true) {
                $submitted && $this->managing => 'Changes saved and sent for review. Your experience goes back live once approved.',
                $submitted => 'Your experience has been submitted for review. We’ll let you know when it’s live.',
                default => 'Changes saved.',
            });

            return $this->redirectRoute('provider.activity-manage', $activity, navigate: false);
        } catch (ValidationException $e) {
            // The publication checklist (ActivityPublicationService).
            $this->addError('save', collect($e->errors())->flatten()->first());
        } catch (Throwable $e) {
            Log::error('Experience save failed', ['activity_id' => $this->activity?->id, 'error' => $e->getMessage()]);
            $this->addError('save', 'We couldn’t save your experience. Please try again, or contact support if it keeps happening.');
        }
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function render()
    {
        $saved = $this->activity?->exists;

        return view('livewire.activity.activity-create', [
            'steps' => self::STEPS,
            'categories' => ActivityCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'languageOptions' => self::LANGUAGES,
            'fitnessOptions' => self::FITNESS,
            'accessibilityOptions' => self::ACCESSIBILITY,
            'cutoffOptions' => self::CUTOFFS + [(string) $this->booking_cutoff_minutes => $this->minutesLabel($this->booking_cutoff_minutes).' before'],
            'noticeOptions' => self::NOTICE + [(string) $this->minimum_notice_minutes => $this->minutesLabel($this->minimum_notice_minutes)],
            'horizonOptions' => self::HORIZONS,
            'dayNames' => self::DAYS,
            'countries' => Countries::all(),
            'currencies' => Currencies::all(),
            'timezones' => $this->timezoneOptions(),
            'symbol' => Currencies::symbol($this->currency),
            'maps' => ['key' => config('services.google.maps_key'), 'id' => config('services.google.maps_id')],
            'cancellationTypes' => HotelCreate::CANCELLATION,
            'sceneCount' => $saved ? $this->activity->scenes()->count() : 0,
            'sceneFilms' => $saved ? $this->activity->scenes()->where('media_type', 'video')->count() : 0,
            'upcomingSessions' => $saved ? $this->activity->sessions()->where('status', 'scheduled')->where('starts_at', '>=', now())->count() : 0,
            'suggestedScenes' => self::SUGGESTED_SCENES,
            'durationMinutes' => $this->durationMinutes(),
            'timelineMinutes' => $this->timelineMinutes(),
            'weeklySessions' => collect($this->schedules)->sum(fn ($row) => count((array) ($row['days'] ?? []))),
            'status' => $this->activity?->status,
        ])->title(($this->managing ? 'Manage ' : 'Add ').($this->name ?: 'an experience').' · HolidayHub');
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:255',
                'slug' => ['required', 'string', 'max:255', Rule::unique('activities', 'slug')->ignore($this->activity?->id)],
                'category_id' => ['required', 'integer', Rule::exists('activity_categories', 'id')->where('is_active', true)],
                'short_description' => 'required|string|min:20|max:300',
                'description' => 'required|string|min:40|max:5000',
                'highlights' => 'array|max:12',
                'highlights.*' => 'nullable|string|max:120',
                'languages' => 'required|array|min:1',
                'languages.*' => [Rule::in(array_keys(self::LANGUAGES))],
            ],
            2 => [
                'country_code' => ['required', Rule::in(Countries::codes())],
                'destination_id' => 'required|integer|exists:destinations,id',
                'meeting_point' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'meeting_instructions' => 'nullable|string|max:1000',
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
                'itinerary' => 'array|max:20',
                'itinerary.*.title' => 'required|string|max:255',
                'itinerary.*.description' => 'nullable|string|max:1000',
                'itinerary.*.minutes' => 'nullable|integer|min:1|max:1440',
                'duration_hours' => ['required', 'integer', 'min:0', 'max:240', function ($attribute, $value, $fail) {
                    if ($this->durationMinutes() < 15) {
                        $fail('How long does the experience last? At least 15 minutes.');
                    }
                }],
                'duration_mins' => 'nullable|integer|min:0|max:59',
                'inclusions.*' => 'nullable|string|max:160',
                'exclusions.*' => 'nullable|string|max:160',
                'bring.*' => 'nullable|string|max:160',
            ],
            6 => [
                'capacity' => 'required|integer|min:1|max:500',
                'min_age' => 'nullable|integer|min:0|max:99',
                'max_age' => 'nullable|integer|min:1|max:120|gte:min_age',
                'accessibility' => 'array',
                'accessibility.*' => [Rule::in(array_keys(self::ACCESSIBILITY))],
                'fitness' => ['required', Rule::in(array_keys(self::FITNESS))],
                'requirements.*' => 'nullable|string|max:200',
                'weather_dependent' => 'boolean',
                'weather_cancellation_policy' => 'nullable|string|max:1000',
                'safety_instructions' => 'nullable|string|max:2000',
                'cancellation' => 'required|string|max:500',
            ],
            7 => [
                'currency' => ['required', Rule::in(Currencies::codes())],
                'base_price' => 'required|numeric|min:1|max:99999999',
                'timezone' => 'required|timezone',
                'schedules' => 'required|array|min:1|max:20',
                'schedules.*.days' => 'required|array|min:1',
                'schedules.*.days.*' => 'integer|between:0,6',
                'schedules.*.start' => 'required|date_format:H:i',
                'schedules.*.end' => 'required|date_format:H:i|different:schedules.*.start',
                'schedules.*.capacity' => 'required|integer|min:1|max:500',
                'booking_cutoff_minutes' => 'nullable|integer|min:0|max:43200',
                'minimum_notice_minutes' => 'required|integer|min:0|max:43200',
                'horizon_weeks' => ['required', Rule::in(array_keys(self::HORIZONS))],
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'category_id.required' => 'Choose the kind of experience.',
            'short_description.required' => 'Write the one line that makes guests want to go.',
            'short_description.min' => 'Give the hook a little more: at least 20 characters.',
            'description.min' => 'Tell guests a little more: at least 40 characters.',
            'languages.required' => 'Choose at least one language you guide in.',
            'country_code.required' => 'Choose the country the experience is in.',
            'destination_id.required' => 'Choose a destination, or add yours if it isn’t listed.',
            'meeting_point.required' => 'Name the place guests meet you.',
            'address.required' => 'Add the address or a landmark for the meeting point.',
            'latitude.required' => 'Drop a pin on the meeting point on the map.',
            'longitude.required' => 'Drop a pin on the meeting point on the map.',
            'cover.required' => 'Add a cover photo; it’s the first thing guests see.',
            'itinerary.*.title.required' => 'Give this part of the plan a title, or remove it.',
            'max_age.gte' => 'The oldest age must be at or above the youngest.',
            'base_price.required' => 'Set the price per person.',
            'base_price.min' => 'The price must be above zero.',
            'schedules.required' => 'Add at least one time the experience runs.',
            'schedules.*.days.required' => 'Pick the days this time runs.',
            'schedules.*.start.required' => 'When does it start?',
            'schedules.*.end.required' => 'When does it end?',
            'schedules.*.end.different' => 'The end time must differ from the start.',
        ];
    }

    private function stepForErrors(array $keys): int
    {
        foreach ([
            1 => ['name', 'slug', 'category_id', 'short_description', 'description', 'highlights', 'languages'],
            2 => ['destination_id', 'country_code', 'city', 'meeting_point', 'address', 'meeting_instructions', 'latitude', 'longitude', 'destinationSearch'],
            3 => ['cover', 'coverVideo', 'new_gallery'],
            5 => ['itinerary', 'duration_', 'inclusions', 'exclusions', 'bring'],
            6 => ['capacity', 'min_age', 'max_age', 'accessibility', 'fitness', 'requirements', 'weather_', 'safety_instructions', 'cancellation'],
        ] as $step => $prefixes) {
            foreach ($keys as $key) {
                if (Str::startsWith($key, $prefixes)) {
                    return $step;
                }
            }
        }

        return 7;
    }

    private function minutesLabel(mixed $minutes): string
    {
        $minutes = (int) $minutes;

        return $minutes % 60 === 0 ? ($minutes / 60).' '.Str::plural('hour', $minutes / 60) : $minutes.' minutes';
    }

    private function durationMinutes(): int
    {
        return max(0, (int) $this->duration_hours) * 60 + max(0, (int) $this->duration_mins);
    }

    private function timelineMinutes(): int
    {
        return (int) collect($this->itinerary)->sum(fn ($item) => max(0, (int) ($item['minutes'] ?? 0)));
    }

    private function lines(array $list): array
    {
        return collect($list)->map(fn ($line) => trim((string) $line))->filter()->unique()->values()->all();
    }

    /**
     * Create or update the experience row (a draft until submitted), and with
     * `withDetails` its meeting point, languages, plan and requirements.
     */
    private function persistCore(bool $withDetails = false): void
    {
        $category = $this->category_id ? ActivityCategory::find($this->category_id) : null;
        $minutes = $this->durationMinutes();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'category_id' => $category?->id,
            'type' => $category?->name,
            'destination_id' => $this->destination_id,
            'short_description' => trim($this->short_description),
            'description' => trim($this->description),
            'highlights' => $this->lines($this->highlights),
            'inclusions' => $this->lines($this->inclusions),
            'exclusions' => $this->lines($this->exclusions),
            'duration_minutes' => $minutes > 0 ? $minutes : null,
            'capacity' => max(1, (int) $this->capacity),
            'min_age' => filled($this->min_age) ? (int) $this->min_age : null,
            'max_age' => filled($this->max_age) ? (int) $this->max_age : null,
            'accessibility' => array_values(array_intersect_key(self::ACCESSIBILITY, array_flip($this->accessibility))),
            'weather_dependent' => $this->weather_dependent,
            'weather_cancellation_policy' => $this->weather_dependent ? (trim($this->weather_cancellation_policy) ?: null) : null,
            'safety_instructions' => trim($this->safety_instructions) ?: null,
            'attributes' => [
                'cancellation_policy' => ($this->prefilled['cancellation'] ?? null) === $this->cancellation ? null : (trim($this->cancellation) ?: null),
                'booking_horizon_weeks' => (int) $this->horizon_weeks,
                'city' => trim($this->city) ?: null,
            ],
        ];

        if (! $this->activity) {
            $data['setup_step'] = $this->step;
        }

        $creator = app(ActivityCreator::class);
        $isNew = ! $this->activity;
        $this->activity = $creator->saveDraft($data, $this->activity);

        // The currency and timezone follow the country from the start, so a
        // resumed draft keeps the suggestion. Saved quietly (operational).
        if ($isNew || $this->activity->currency !== $this->currency || $this->activity->timezone !== $this->timezone) {
            $this->activity = $creator->savePricing($this->activity, ['currency' => $this->currency, 'timezone' => $this->timezone]);
        }

        if ($withDetails) {
            $this->persistDetails();
        }
    }

    private function persistDetails(): void
    {
        if (! $this->activity) {
            return;
        }

        $fitness = self::FITNESS[$this->fitness] ?? self::FITNESS['easy'];
        $keepUnset = fn (string $field, $value) => array_key_exists($field, $this->prefilled) && $this->prefilled[$field] === $value;
        $requirements = array_merge(
            $keepUnset('fitness', $this->fitness) ? [] : [['type' => 'fitness', 'title' => 'Fitness: '.$fitness[0], 'description' => $fitness[1], 'required' => false]],
            array_map(fn ($line) => ['type' => 'requirement', 'title' => $line, 'required' => true], $this->lines($this->requirements)),
            array_map(fn ($line) => ['type' => 'bring', 'title' => $line, 'required' => false], $this->lines($this->bring)),
        );

        $details = [
            'itinerary' => $this->itinerary,
            'requirements' => $requirements,
        ];

        $languages = array_values(array_intersect(array_keys(self::LANGUAGES), $this->languages));
        if (! $keepUnset('languages', $languages)) {
            $details['languages'] = $languages;
        }

        if (filled($this->meeting_point) || filled($this->address) || filled($this->latitude)) {
            $details['meeting_point'] = [
                'name' => $this->meeting_point,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'instructions' => $this->meeting_instructions,
            ];
        }

        $this->activity = app(ActivityCreator::class)->saveDetails($this->activity, $details);
    }

    private function persistMedia(): void
    {
        $activity = app(ActivityCreator::class)->saveMedia($this->activity, [
            'cover' => $this->cover,
            'cover_path' => $this->cover_path,
            'cover_video' => $this->coverVideo,
            'cover_video_path' => $this->cover_video_path,
            'gallery_paths' => $this->gallery_paths,
            'new_gallery' => array_values(array_filter($this->new_gallery)),
        ]);

        $this->activity = $activity;
        $this->cover = null;
        $this->coverVideo = null;
        $this->new_gallery = [];
        $this->cover_path = $activity->thumbnail;
        $this->cover_video_path = $activity->cover_video;
        $this->gallery_paths = array_values((array) ($activity->gallery ?? []));
    }

    private function persistPricing(): void
    {
        $this->activity = app(ActivityCreator::class)->savePricing($this->activity, [
            'base_price' => $this->base_price,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'booking_cutoff_minutes' => filled($this->booking_cutoff_minutes) ? (int) $this->booking_cutoff_minutes : null,
            'minimum_notice_minutes' => (int) $this->minimum_notice_minutes,
        ]);
    }

    private function persistSchedule(): void
    {
        $result = app(ActivityCreator::class)->saveSchedule($this->activity, $this->schedules, (int) $this->horizon_weeks, $this->timezone);

        $this->scheduleNotice = $result['kept'] > 0
            ? "{$result['kept']} booked ".Str::plural('session', $result['kept']).' on times you removed stay on your calendar; contact those guests if you need to move them.'
            : null;
    }

    private function persistTimes(): void
    {
        // The booking window lives in `attributes`, with the core.
        $this->persistCore();
        $this->persistPricing();
        $this->persistSchedule();
    }

    private function zoneFor(?string $code): ?string
    {
        if (blank($code)) {
            return null;
        }

        try {
            return DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, Str::upper($code))[0] ?? null;
        } catch (Throwable) {
            return null;
        }
    }

    /** The country's own zones first, then every zone. */
    private function timezoneOptions(): array
    {
        $local = [];
        if ($this->country_code !== '') {
            try {
                $local = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, Str::upper($this->country_code));
            } catch (Throwable) {
                $local = [];
            }
        }

        return array_values(array_unique(array_merge($local, [$this->timezone], DateTimeZone::listIdentifiers())));
    }

    // ── Progress ─────────────────────────────────────────────────────────

    private function resumableDraft(): ?Activity
    {
        return Activity::query()
            ->where('provider_id', Auth::user()->provider->id)
            ->whereNotNull('setup_step')
            ->latest('updated_at')
            ->first();
    }

    private function rememberStep(int $step): void
    {
        if ($this->managing || ! $this->activity) {
            return;
        }

        $reached = max((int) $this->activity->setup_step, $step);

        if ((int) $this->activity->setup_step !== $reached) {
            $this->activity->updateQuietly(['setup_step' => $reached]);
        }
    }

    private function autosave(callable $save): void
    {
        if ($this->managing || ! $this->activity) {
            return;
        }

        try {
            $save();
        } catch (Throwable $e) {
            Log::warning('Experience autosave skipped', ['activity_id' => $this->activity?->id, 'error' => $e->getMessage()]);
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

        try {
            $this->persistCore();
            $this->rememberStep($this->step);
        } catch (Throwable $e) {
            Log::warning('Experience autosave skipped', ['activity_id' => $this->activity?->id, 'error' => $e->getMessage()]);
        }
    }

    private function autosaveMedia(): void
    {
        $this->autosave(fn () => $this->persistMedia());
    }

    private function loadActivity(Activity $activity): void
    {
        $activity->load(['destination', 'category', 'languages', 'locations', 'itinerary', 'requirements', 'schedules']);

        $this->activity = $activity;
        $point = $activity->locations->firstWhere('type', 'meeting_point');
        $minutes = (int) $activity->duration_minutes;
        $attributes = (array) ($activity->getAttribute('attributes') ?? []);
        $requirements = $activity->requirements->sortBy('id');
        $fitness = $requirements->firstWhere('type', 'fitness');
        $countryCode = (string) Countries::codeFor($activity->destination?->country);

        $this->fill([
            'name' => (string) $activity->name,
            'slug' => (string) $activity->slug,
            'category_id' => $activity->category_id,
            'short_description' => (string) $activity->short_description,
            'description' => (string) $activity->description,
            'highlights' => $this->padded((array) ($activity->highlights ?? []), 3),
            'languages' => $activity->languages->pluck('language_code')->map(fn ($code) => Str::lower($code))->values()->all(),
            'destination_id' => $activity->destination_id,
            'country_code' => $countryCode,
            'country' => (string) (Countries::name($countryCode) ?? $activity->destination?->country),
            'latitude' => $point?->latitude,
            'longitude' => $point?->longitude,
            'meeting_point' => (string) $point?->name,
            'address' => (string) $point?->address,
            'meeting_instructions' => (string) $point?->instructions,
            'city' => (string) ($attributes['city'] ?? $activity->destination?->city ?? ''),
            'cover_path' => $activity->thumbnail,
            'cover_video_path' => $activity->cover_video,
            'gallery_paths' => array_values((array) ($activity->gallery ?? [])),
            'itinerary' => $activity->itinerary->map(fn ($item) => [
                'title' => (string) $item->title,
                'description' => (string) $item->description,
                'minutes' => $item->duration_minutes,
            ])->values()->all(),
            'duration_hours' => intdiv($minutes, 60),
            'duration_mins' => $minutes % 60,
            'inclusions' => $this->padded((array) ($activity->inclusions ?? []), 1),
            'exclusions' => $this->padded((array) ($activity->exclusions ?? []), 1),
            'bring' => $this->padded($requirements->where('type', 'bring')->pluck('title')->all(), 1),
            'requirements' => $this->padded($requirements->whereNotIn('type', ['bring', 'fitness'])->pluck('title')->all(), 1),
            'fitness' => $fitness ? (collect(self::FITNESS)->search(fn ($option) => 'Fitness: '.$option[0] === $fitness->title) ?: 'easy') : 'easy',
            'capacity' => (int) $activity->capacity ?: 10,
            'min_age' => $activity->min_age,
            'max_age' => $activity->max_age,
            'accessibility' => collect((array) ($activity->accessibility ?? []))->map(fn ($label) => array_search($label, self::ACCESSIBILITY, true))->filter()->values()->all(),
            'weather_dependent' => (bool) $activity->weather_dependent,
            'weather_cancellation_policy' => (string) $activity->weather_cancellation_policy,
            'safety_instructions' => (string) $activity->safety_instructions,
            'cancellation' => (string) ($attributes['cancellation_policy'] ?? ''),
            'currency' => $activity->getCurrency(),
            'currencyChosen' => true,
            'base_price' => $activity->base_price !== null ? (float) $activity->base_price : null,
            'timezone' => (string) ($activity->timezone ?: 'UTC'),
            'timezoneChosen' => true,
            'booking_cutoff_minutes' => $activity->booking_cutoff_minutes === null ? '' : (string) $activity->booking_cutoff_minutes,
            'minimum_notice_minutes' => (string) (int) $activity->minimum_notice_minutes,
            'horizon_weeks' => (int) ($attributes['booking_horizon_weeks'] ?? 12),
        ]);

        // A cutoff or notice outside the presets is kept as is: render() adds it as an option.
        if (! array_key_exists((int) $this->horizon_weeks, self::HORIZONS)) {
            $this->horizon_weeks = 12;
        }

        $this->prefilled = [];
        if ($this->languages === []) {
            $this->languages = $this->prefilled['languages'] = ['en'];
        }
        if (! $fitness) {
            $this->prefilled['fitness'] = $this->fitness;
        }
        if (blank($this->cancellation)) {
            $this->cancellation = $this->prefilled['cancellation'] = HotelCreate::CANCELLATION['moderate'];
        }
        $this->cancellation_type = array_search($this->cancellation, HotelCreate::CANCELLATION, true) ?: 'custom';

        // One timetable row per start/end/capacity, with its weekdays.
        $this->schedules = $activity->schedules
            ->where('is_active', true)
            ->whereNull('activity_option_id')
            ->groupBy(fn ($schedule) => substr((string) $schedule->start_time, 0, 5).'|'.substr((string) $schedule->end_time, 0, 5).'|'.$schedule->capacity)
            ->map(fn ($group, $key) => [
                'days' => $group->pluck('day_of_week')->map(fn ($day) => (string) $day)->sort()->values()->all(),
                'start' => explode('|', $key)[0],
                'end' => explode('|', $key)[1],
                'capacity' => (int) explode('|', $key)[2],
            ])
            ->sortBy('start')
            ->values()
            ->all();

        if ($this->schedules === []) {
            $this->schedules = [['days' => [], 'start' => '09:00', 'end' => '', 'capacity' => (int) $this->capacity]];
        }
    }

    private function padded(array $lines, int $min): array
    {
        $lines = array_values(array_map('strval', $lines));

        return array_pad($lines, max($min, count($lines)), '');
    }
}
