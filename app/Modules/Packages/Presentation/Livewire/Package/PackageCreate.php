<?php

namespace App\Modules\Packages\Presentation\Livewire\Package;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Presentation\Support\TripPresenter;
use App\Modules\Destinations\Application\Services\DestinationResolver;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Packages\Application\Services\PackageBuilder;
use App\Modules\Packages\Application\Services\PackageListingSearch;
use App\Modules\Packages\Application\Services\PackagePricing;
use App\Modules\Packages\Application\Services\PackagePublicationService;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Domain\Models\PackageComponent;
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
 * Build a package (a multi-day journey made of listings already live on
 * HolidayHub), then keep managing it.
 *
 * Create mode walks the agent through six steps, saving a draft as it goes
 * and submitting for review at the end; an unfinished package reopens where
 * it was left (`setup_step`), unless `?new=1`. Manage mode
 * (/agent/packages/{slug}/manage) opens the same steps as tabs.
 *
 * The journey is day by day: stays (a hotel room type, a villa or a B&B for
 * some nights) and experiences (optionally a specific option) found by
 * searching published listings, plus transfers, free time and notes the
 * agent prices themselves. Prices are never typed for listings: the "Price &
 * guests" step shows a live sample quote (PackagePricing) with the agent's
 * margin on top.
 */
#[Layout('layouts.discovery')]
class PackageCreate extends Component
{
    use WithFileUploads;

    public const STEPS = [
        1 => ['label' => 'The journey', 'hint' => 'Name, story, where and how long'],
        2 => ['label' => 'Day by day', 'hint' => 'Stays, experiences and extras'],
        3 => ['label' => 'Photos & film', 'hint' => 'Lead with film, then photos'],
        4 => ['label' => 'The journey reel', 'hint' => 'Let guests live it first'],
        5 => ['label' => 'Price & guests', 'hint' => 'Currency, margin, group size'],
        6 => ['label' => 'Review', 'hint' => 'Check and submit'],
    ];

    public const SUGGESTED_SCENES = 5;

    private const LAST = 6;

    public int $step = 1;

    public bool $managing = false;

    public ?Package $package = null;

    public bool $resumed = false;

    // The journey
    public string $name = '';

    public string $slug = '';

    public string $short_description = '';

    public string $full_description = '';

    public ?int $destination_id = null;

    /** @var array<int, int> Other destinations the journey visits. */
    public array $extra_destinations = [];

    public string $destinationSearch = '';

    public $duration_days = 5;

    // Day by day
    /** @var array<int, array<string, mixed>> */
    public array $components = [];

    public array $inclusions = [''];

    public array $exclusions = [''];

    /** The day the listing picker is open for (null when closed). */
    public ?int $addingDay = null;

    public string $search = '';

    public string $searchType = 'all';

    public $searchDestination = '';

    // Photos & film
    public $cover = null;

    public ?string $cover_path = null;

    public $coverVideo = null;

    public ?string $cover_video_path = null;

    public array $gallery_paths = [];

    public array $new_gallery = [];

    public array $galleryUpload = [];

    public ?string $mediaNotice = null;

    // Price & guests
    public string $currency = 'USD';

    public string $markup_type = 'percent';

    public $markup_value = 15;

    public $min_guests = 1;

    public $max_guests = 6;

    public $sampleGuests = 2;

    public string $sampleDate = '';

    public function mount(?Package $package = null)
    {
        $user = Auth::user();

        if ($package?->exists) {
            Gate::authorize('update', $package);
            $this->loadPackage($package);

            if ($package->setup_step !== null) {
                $this->step = max(1, min(self::LAST, (int) $package->setup_step));
            } else {
                $this->managing = true;
            }

            return;
        }

        if (! $user->hasRole('agent') || ! $user->agent) {
            return $this->redirectRoute('agent.apply', navigate: false);
        }

        if (! request()->boolean('new') && ($draft = $this->resumableDraft())) {
            $this->loadPackage($draft);
            $this->step = max(1, min(self::LAST, (int) $draft->setup_step));
            $this->resumed = true;

            return;
        }

        $this->currency = Currencies::forCountry($this->countryCodeFor($user->agent->country)) ?: 'USD';
        if (! config('packages.rates.'.$this->currency)) {
            $this->currency = 'USD';
        }
        $this->sampleDate = today()->addDays((int) config('packages.default_lead_days', 30))->toDateString();
    }

    /** Save progress as the agent goes (create mode only; manage waits for "Save changes"). */
    public function updated(string $property): void
    {
        if ($this->managing) {
            return;
        }

        if (in_array($property, ['name', 'short_description', 'full_description', 'duration_days'], true) || Str::startsWith($property, ['inclusions', 'exclusions'])) {
            $this->autosaveCore();
        } elseif (Str::startsWith($property, 'components')) {
            $this->autosave(fn () => $this->persistComponents());
        } elseif (in_array($property, ['currency', 'markup_type', 'markup_value', 'min_guests', 'max_guests'], true)) {
            $this->autosave(fn () => $this->persistPricing());
        }
    }

    // ── Steps ────────────────────────────────────────────────────────────

    public function goTo(int $step): void
    {
        if (! isset(self::STEPS[$step]) || (! $this->managing && $step > $this->reachedStep())) {
            return;
        }

        $this->step = $step;
    }

    public function nextStep(): void
    {
        if ($rules = $this->rulesFor($this->step)) {
            $this->validate($rules, $this->messages());
        }

        match ($this->step) {
            1 => $this->persistCore(),
            2 => [$this->persistCore(), $this->persistComponents()],
            3 => $this->persistMedia(),
            5 => $this->persistPricing(),
            default => null,
        };

        $this->step = min(self::LAST, $this->step + 1);
        $this->addingDay = null;
        $this->rememberStep($this->step);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
        $this->addingDay = null;
    }

    public function updatedName(string $value): void
    {
        if ($this->managing) {
            return;
        }

        $base = Str::slug($value) ?: 'journey';
        $slug = $base;
        $n = 1;

        while (Package::withTrashed()->where('slug', $slug)->when($this->package, fn ($q) => $q->whereKeyNot($this->package->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        $this->slug = $slug;
    }

    public function updatedDurationDays(): void
    {
        $days = max(1, min(30, (int) $this->duration_days));
        foreach ($this->components as $i => $row) {
            if ((int) $row['day'] > $days) {
                $this->components[$i]['day'] = $days;
            }
        }
    }

    public function updatedCurrency(): void
    {
        foreach ($this->components as $i => $row) {
            if (! in_array($row['kind'], ['stay', 'experience'], true)) {
                $this->components[$i]['currency'] = $this->currency;
            }
        }
    }

    // ── Destinations ─────────────────────────────────────────────────────

    #[Computed]
    public function destinationResults()
    {
        return trim($this->destinationSearch) === ''
            ? collect()
            : app(DestinationResolver::class)->search($this->destinationSearch, null, 6);
    }

    public function addDestination(int $id): void
    {
        $destination = Destination::findOrFail($id);

        if ($this->destination_id === null) {
            $this->destination_id = $destination->id;
        } elseif ($destination->id !== $this->destination_id && ! in_array($destination->id, $this->extra_destinations, true)) {
            $this->extra_destinations[] = $destination->id;
        }

        $this->destinationSearch = '';
        $this->autosaveCore();
    }

    public function removeDestination(int $id): void
    {
        if ($this->destination_id === $id) {
            $this->destination_id = array_shift($this->extra_destinations);
        } else {
            $this->extra_destinations = array_values(array_diff($this->extra_destinations, [$id]));
        }

        $this->autosaveCore();
    }

    /** Destinations of the listings already in the journey, offered as one-tap picks. */
    private function suggestedDestinations(array $cards)
    {
        $chosen = array_filter(array_merge([$this->destination_id], $this->extra_destinations));

        return Destination::query()
            ->whereIn('id', collect($cards)->pluck('destination_id')->filter()->unique())
            ->whereNotIn('id', $chosen ?: [0])
            ->get(['id', 'name']);
    }

    // ── Day by day ───────────────────────────────────────────────────────

    public function openPicker(int $day): void
    {
        $this->addingDay = $this->addingDay === $day ? null : $day;
        if ($this->searchDestination === '' && $this->destination_id) {
            $this->searchDestination = (string) $this->destination_id;
        }
    }

    #[Computed]
    public function listingResults()
    {
        if ($this->addingDay === null) {
            return collect();
        }

        return app(PackageListingSearch::class)->search(
            $this->search,
            array_key_exists($this->searchType, PackageListingSearch::TYPES) ? $this->searchType : 'all',
            filled($this->searchDestination) ? (int) $this->searchDestination : null,
        );
    }

    /**
     * Add a listing to the open day: `type` is room_type, villa,
     * bed_and_breakfast or activity; an activity may name an option.
     */
    public function addListing(string $type, int $id, ?int $optionId = null): void
    {
        if ($this->addingDay === null) {
            return;
        }

        $day = $this->addingDay;
        $stay = in_array($type, PackageComponent::STAY_TYPES, true);

        $this->components[] = [
            'day' => $day,
            'kind' => $stay ? 'stay' : 'experience',
            'type' => $stay ? $type : 'activity',
            'listing_id' => $id,
            'option_id' => $optionId,
            'nights' => $stay ? max(1, min(3, (int) $this->duration_days - $day + 0)) ?: 1 : null,
            'quantity' => 1,
            'title' => '',
            'description' => '',
            'price' => null,
            'currency' => null,
        ];

        $this->sortComponents();
        $this->addingDay = null;
        $this->updated('components');
    }

    public function addExtra(int $day, string $kind): void
    {
        if (! in_array($kind, ['transfer', 'free', 'note'], true)) {
            return;
        }

        $this->components[] = [
            'day' => max(1, $day),
            'kind' => $kind,
            'type' => null,
            'listing_id' => null,
            'option_id' => null,
            'nights' => null,
            'quantity' => 1,
            'title' => ['transfer' => 'Private transfer', 'free' => 'Time to yourself', 'note' => ''][$kind],
            'description' => '',
            'price' => null,
            'currency' => $this->currency,
        ];

        $this->sortComponents();
        $this->updated('components');
    }

    public function removeComponent(int $index): void
    {
        unset($this->components[$index]);
        $this->components = array_values($this->components);
        $this->updated('components');
    }

    /** Swap with the previous or next part of the same day. */
    public function moveComponent(int $index, int $direction): void
    {
        $day = $this->components[$index]['day'] ?? null;
        if ($day === null) {
            return;
        }

        $sameDay = collect($this->components)->filter(fn ($row) => (int) $row['day'] === (int) $day)->keys()->values();
        $at = $sameDay->search($index);
        $to = $sameDay[$at + ($direction < 0 ? -1 : 1)] ?? null;

        if ($to === null) {
            return;
        }

        [$this->components[$index], $this->components[$to]] = [$this->components[$to], $this->components[$index]];
        $this->updated('components');
    }

    public function addLine(string $list): void
    {
        if (in_array($list, ['inclusions', 'exclusions'], true) && count($this->{$list}) < 12) {
            $this->{$list}[] = '';
        }
    }

    public function removeLine(string $list, int $index): void
    {
        if (! in_array($list, ['inclusions', 'exclusions'], true)) {
            return;
        }

        unset($this->{$list}[$index]);
        $this->{$list} = array_values($this->{$list}) ?: [''];
        $this->updated($list);
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

    /** "Use media from the listings in this journey." */
    public function useListingMedia(): void
    {
        if (! $this->package) {
            return;
        }

        $this->persistComponents();
        $media = app(PackageBuilder::class)->listingMedia($this->package);
        $added = 0;

        if (blank($this->cover_path) && ! $this->cover && $media['cover']) {
            $this->cover_path = $media['cover'];
            $added++;
        }
        if (blank($this->cover_video_path) && ! $this->coverVideo && $media['film']) {
            $this->cover_video_path = $media['film'];
            $added++;
        }
        foreach ($media['gallery'] as $path) {
            if (! in_array($path, $this->gallery_paths, true) && $path !== $this->cover_path && count($this->gallery_paths) < 24) {
                $this->gallery_paths[] = $path;
                $added++;
            }
        }

        $this->mediaNotice = $added
            ? "Added {$added} ".Str::plural('photo or film', $added).' from the listings in your journey. Remove any you don’t want.'
            : 'Nothing new to add: add stays and experiences with photos first, or upload your own.';

        if ($this->managing) {
            return;
        }
        $this->autosaveMedia();
    }

    // ── Save ─────────────────────────────────────────────────────────────

    public function save()
    {
        $rules = collect([1, 2, 3, 5])->flatMap(fn ($step) => $this->rulesFor($step))->all();

        try {
            $this->validate($rules, $this->messages());
        } catch (ValidationException $e) {
            $this->step = $this->stepForErrors(array_keys($e->errors()));
            throw $e;
        }

        try {
            $this->persistCore();
            $this->persistComponents();
            $this->persistMedia();
            $this->persistPricing();
            $package = $this->package->refresh();
            $publication = app(PackagePublicationService::class);

            if (in_array($package->status, ['draft', 'rejected'], true)) {
                try {
                    $package = $publication->submit($package, Auth::user());
                } catch (ValidationException $e) {
                    // Saved, but not ready (or the agent isn't verified yet):
                    // stay on the review step with the reason.
                    $package->updateQuietly(['setup_step' => self::LAST]);
                    $this->step = self::LAST;
                    $this->addError('save', collect($e->errors())->flatten()->first());

                    return null;
                }
                session()->flash('success', $this->managing
                    ? 'Changes saved and sent for review. Your journey goes back live once approved.'
                    : 'Your journey has been submitted for review. We’ll let you know when it’s live.');
            } else {
                if ($package->setup_step !== null) {
                    $package->updateQuietly(['setup_step' => null]);
                }
                session()->flash('success', $package->status === 'pending_review'
                    ? 'Changes saved. They go live once our team has reviewed them.'
                    : 'Changes saved.');
            }

            return $this->redirectRoute('agent.packages.manage', $package->slug, navigate: false);
        } catch (Throwable $e) {
            Log::error('Package save failed', ['package_id' => $this->package?->id, 'error' => $e->getMessage()]);
            $this->addError('save', 'We couldn’t save your journey. Please try again, or contact support if it keeps happening.');
        }

        return null;
    }

    // ── Rendering ────────────────────────────────────────────────────────

    public function render()
    {
        $saved = (bool) $this->package?->exists;
        $cards = $this->componentCards();
        $quote = $this->step === 5 || $this->step === 6 ? $this->sampleQuote() : null;
        $chosenDestinations = Destination::query()->whereIn('id', array_filter(array_merge([$this->destination_id], $this->extra_destinations)) ?: [0])->get(['id', 'name', 'country'])->keyBy('id');

        return view('livewire.package.package-create', [
            'steps' => self::STEPS,
            'cards' => $cards,
            'days' => max(1, min(30, (int) $this->duration_days)),
            'chosenDestinations' => collect(array_filter(array_merge([$this->destination_id], $this->extra_destinations)))->map(fn ($id) => $chosenDestinations[$id] ?? null)->filter()->values(),
            'suggestedDestinations' => $this->step === 1 ? $this->suggestedDestinations($cards) : collect(),
            'allDestinations' => $this->step === 2 ? Destination::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'searchTypes' => PackageListingSearch::TYPES,
            'kinds' => PackageComponent::KINDS,
            'currencies' => collect(Currencies::all())->filter(fn ($label, $code) => config('packages.rates.'.$code))->all(),
            'symbol' => Currencies::symbol($this->currency),
            'quote' => $quote,
            'departures' => $this->step === 5 && $saved ? $this->departures() : [],
            'requirements' => $saved && $this->step === 6 ? app(PackagePublicationService::class)->requirements($this->package->fresh()) : [],
            'verified' => (bool) Auth::user()->agent?->isVerified() || ($this->package && $this->package->agent_id !== Auth::user()->agent?->id),
            'sceneCount' => $saved ? $this->package->scenes()->count() : 0,
            'suggestedScenes' => self::SUGGESTED_SCENES,
            'status' => $this->package?->status,
            'rejection' => in_array($this->package?->status, ['rejected', 'suspended'], true) ? $this->package->rejection_reason : null,
            'reached' => $this->reachedStep(),
        ])->title(($this->managing ? 'Manage ' : 'Build ').($this->name ?: 'a journey').' · HolidayHub');
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function rulesFor(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|max:120',
                'slug' => ['required', 'string', 'max:160', Rule::unique('packages', 'slug')->ignore($this->package?->id)],
                'short_description' => 'required|string|min:20|max:300',
                'full_description' => 'required|string|min:40|max:5000',
                'destination_id' => 'required|integer|exists:destinations,id',
                'extra_destinations.*' => 'integer|exists:destinations,id',
                'duration_days' => 'required|integer|min:1|max:30',
            ],
            2 => [
                'components' => ['required', 'array', 'max:60', function ($attribute, $value, $fail) {
                    if (! collect($value)->contains(fn ($row) => in_array($row['kind'] ?? null, ['stay', 'experience'], true))) {
                        $fail('Add at least one stay or experience from HolidayHub.');
                    }
                }],
                'components.*.day' => 'required|integer|min:1|max:'.max(1, (int) $this->duration_days),
                'components.*.nights' => 'nullable|integer|min:1|max:30',
                'components.*.title' => 'nullable|string|max:160',
                'components.*.description' => 'nullable|string|max:1000',
                'components.*.price' => 'nullable|numeric|min:0|max:99999999',
                'components.*.quantity' => 'nullable|integer|min:1|max:50',
                'inclusions.*' => 'nullable|string|max:160',
                'exclusions.*' => 'nullable|string|max:160',
            ],
            3 => [
                'cover' => [Rule::requiredIf(blank($this->cover_path) && blank($this->cover_video_path) && ! $this->coverVideo), 'nullable', 'image', 'max:6144'],
                'coverVideo' => 'nullable|file|mimes:mp4,webm,mov,m4v|max:204800',
                'new_gallery.*' => Media::UPLOAD_RULE,
            ],
            5 => [
                'currency' => ['required', Rule::in(Currencies::codes()), function ($attribute, $value, $fail) {
                    if (! config('packages.rates.'.Str::upper((string) $value))) {
                        $fail("We don’t have a conversion rate for {$value} yet; choose another currency.");
                    }
                }],
                'markup_type' => ['required', Rule::in(['percent', 'fixed'])],
                'markup_value' => ['required', 'numeric', 'min:0', $this->markup_type === 'percent' ? 'max:100' : 'max:99999999'],
                'min_guests' => 'required|integer|min:1|max:50',
                'max_guests' => 'required|integer|min:1|max:50|gte:min_guests',
            ],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'short_description.required' => 'Write the one line that makes guests want to go.',
            'short_description.min' => 'Give the hook a little more: at least 20 characters.',
            'full_description.required' => 'Tell guests the story of the journey.',
            'full_description.min' => 'Tell guests a little more: at least 40 characters.',
            'destination_id.required' => 'Choose where the journey goes.',
            'duration_days.required' => 'How many days is the journey?',
            'components.required' => 'Add at least one stay or experience from HolidayHub.',
            'cover.required' => 'Add a cover photo or a cover film; it’s the first thing guests see.',
            'max_guests.gte' => 'The largest group must be at least the smallest.',
            'markup_value.max' => $this->markup_type === 'percent' ? 'A margin above 100% is too much.' : 'That margin is too large.',
        ];
    }

    private function stepForErrors(array $keys): int
    {
        foreach ([
            1 => ['name', 'slug', 'short_description', 'full_description', 'destination', 'extra_destinations', 'duration_days'],
            2 => ['components', 'inclusions', 'exclusions'],
            3 => ['cover', 'coverVideo', 'new_gallery'],
        ] as $step => $prefixes) {
            foreach ($keys as $key) {
                if (Str::startsWith($key, $prefixes)) {
                    return $step;
                }
            }
        }

        return 5;
    }

    private function lines(array $list): array
    {
        return collect($list)->map(fn ($line) => trim((string) $line))->filter()->unique()->values()->all();
    }

    private function persistCore(): void
    {
        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => trim($this->short_description),
            'full_description' => trim($this->full_description),
            'destination_id' => $this->destination_id,
            'destinations' => $this->extra_destinations,
            'duration_days' => max(1, (int) $this->duration_days),
            'inclusions' => $this->lines($this->inclusions),
            'exclusions' => $this->lines($this->exclusions),
        ];

        $builder = app(PackageBuilder::class);
        $isNew = ! $this->package;

        if ($isNew) {
            $data['setup_step'] = $this->step;
        }

        $this->package = $builder->saveDraft($data, $this->package);

        if ($isNew) {
            $this->persistPricing();
        }
    }

    private function persistComponents(): void
    {
        if (! $this->package) {
            return;
        }

        $this->sortComponents();
        $rows = collect($this->components)->map(fn ($row) => [
            'day' => (int) $row['day'],
            'kind' => $row['kind'],
            'type' => $row['type'] ?? null,
            'listing_id' => $row['listing_id'] ?? null,
            'option_id' => $row['option_id'] ?? null,
            'nights' => $row['nights'] ?? null,
            'quantity' => $row['quantity'] ?? 1,
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'price' => $row['price'] ?? null,
            'currency' => in_array($row['kind'], ['stay', 'experience'], true) ? null : $this->currency,
        ])->all();

        $this->package = app(PackageBuilder::class)->saveComponents($this->package, $rows);
    }

    private function persistMedia(): void
    {
        $package = app(PackageBuilder::class)->saveMedia($this->package, [
            'cover' => $this->cover,
            'cover_path' => $this->cover_path,
            'cover_video' => $this->coverVideo,
            'cover_video_path' => $this->cover_video_path,
            'gallery_paths' => $this->gallery_paths,
            'new_gallery' => array_values(array_filter($this->new_gallery)),
        ]);

        $this->package = $package;
        $this->cover = null;
        $this->coverVideo = null;
        $this->new_gallery = [];
        $this->cover_path = $package->cover_image;
        $this->cover_video_path = $package->cover_video;
        $this->gallery_paths = array_values((array) ($package->gallery ?? []));
    }

    private function persistPricing(): void
    {
        if (! $this->package) {
            return;
        }

        $this->package = app(PackageBuilder::class)->savePricing($this->package, [
            'currency' => $this->currency,
            'markup_type' => $this->markup_type,
            'markup_value' => $this->markup_value,
            'min_guests' => $this->min_guests,
            'max_guests' => $this->max_guests,
        ]);
    }

    private function sortComponents(): void
    {
        $this->components = collect($this->components)
            ->values()
            ->map(fn ($row, $i) => $row + ['_order' => $i])
            ->sortBy([['day', 'asc'], ['_order', 'asc']])
            ->map(fn ($row) => collect($row)->except('_order')->all())
            ->values()
            ->all();
    }

    /**
     * What each row of the journey shows: the listing's name, picture, place
     * and "from" price, read in one query per kind of listing.
     */
    private function componentCards(): array
    {
        $ids = fn (string $type) => collect($this->components)->where('type', $type)->pluck('listing_id')->filter()->unique()->all();
        $rooms = RoomType::with('hotel.accommodation.destination')->whereIn('id', $ids('room_type') ?: [0])->get()->keyBy('id');
        $villas = Villa::with('accommodation.destination')->whereIn('id', $ids('villa') ?: [0])->get()->keyBy('id');
        $bnbs = BedAndBreakfast::with('accommodation.destination')->whereIn('id', $ids('bed_and_breakfast') ?: [0])->get()->keyBy('id');
        $activities = Activity::with(['destination', 'options'])->whereIn('id', $ids('activity') ?: [0])->get()->keyBy('id');

        return collect($this->components)->map(function ($row) use ($rooms, $villas, $bnbs, $activities) {
            $listing = match ($row['type'] ?? null) {
                'room_type' => $rooms[$row['listing_id']] ?? null,
                'villa' => $villas[$row['listing_id']] ?? null,
                'bed_and_breakfast' => $bnbs[$row['listing_id']] ?? null,
                'activity' => $activities[$row['listing_id']] ?? null,
                default => null,
            };

            if (! $listing) {
                return ['listing' => false, 'missing' => in_array($row['kind'], ['stay', 'experience'], true)];
            }

            $owner = $listing instanceof RoomType ? $listing->hotel : $listing;
            $destination = $listing instanceof Activity ? $listing->destination : $owner?->accommodation?->destination;
            $option = $listing instanceof Activity && filled($row['option_id'] ?? null) ? $listing->options->firstWhere('id', (int) $row['option_id']) : null;
            [$price, $unit] = match (true) {
                $listing instanceof RoomType => [(float) $listing->price_per_night, 'night'],
                $listing instanceof Villa => [(float) $listing->avg_price_per_night, 'night'],
                $listing instanceof BedAndBreakfast => [(float) $listing->price_per_night, 'night'],
                default => [(float) ($option?->base_price ?? $listing->base_price), 'person'],
            };

            return [
                'listing' => true,
                'missing' => false,
                'name' => $listing instanceof RoomType ? ($owner?->name ?? 'Hotel') : $listing->name,
                'detail' => $listing instanceof RoomType ? $listing->name : ($option?->name ?? null),
                'kind' => match (true) {
                    $listing instanceof RoomType => 'Hotel',
                    $listing instanceof Villa => 'Villa',
                    $listing instanceof BedAndBreakfast => 'B&B',
                    default => 'Experience',
                },
                'image' => TripPresenter::image($owner, $listing instanceof Activity ? 'activity' : 'hotel', 300),
                'place' => $destination?->name,
                'destination_id' => $destination?->id,
                'from' => Currencies::format($price, ($option ?? $listing)->getCurrency()).' / '.$unit,
                'options' => $listing instanceof Activity ? $listing->options->where('is_active', true)->map(fn ($o) => ['id' => $o->id, 'name' => $o->name])->values()->all() : [],
                'url' => match (true) {
                    $listing instanceof RoomType && $owner => route('hotel-show', $owner),
                    $listing instanceof Villa => route('villa.show', $listing),
                    $listing instanceof BedAndBreakfast => route('bedandbreakfast.show', $listing),
                    $listing instanceof Activity => route('activity.show', $listing),
                    default => null,
                },
            ];
        })->all();
    }

    /** The live sample quote for the "Price & guests" and "Review" steps. */
    private function sampleQuote(): ?array
    {
        if (! $this->package) {
            return null;
        }

        try {
            $package = $this->package->fresh();
            $package->forceFill([
                'currency' => config('packages.rates.'.$this->currency) ? $this->currency : $package->currency,
                'markup_type' => in_array($this->markup_type, ['percent', 'fixed'], true) ? $this->markup_type : 'percent',
                'markup_value' => is_numeric($this->markup_value) ? max(0, (float) $this->markup_value) : 0,
                'min_guests' => max(1, (int) $this->min_guests),
                'max_guests' => max(1, (int) $this->max_guests),
            ]);
            $guests = max(1, min(50, (int) $this->sampleGuests ?: 2));
            $date = $this->sampleDate ?: today()->addDays(30)->toDateString();

            return app(PackagePricing::class)->quote($package, $date, $guests);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function departures(): array
    {
        try {
            return app(PackagePricing::class)->nextDepartures($this->package->fresh(), max(1, (int) $this->sampleGuests ?: 2), 4);
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    // ── Progress ─────────────────────────────────────────────────────────

    private function reachedStep(): int
    {
        return $this->managing ? self::LAST : max($this->step, (int) ($this->package?->setup_step ?? 1));
    }

    private function resumableDraft(): ?Package
    {
        return Package::query()
            ->where('agent_id', Auth::user()->agent->id)
            ->whereNotNull('setup_step')
            ->latest('updated_at')
            ->first();
    }

    private function rememberStep(int $step): void
    {
        if ($this->managing || ! $this->package) {
            return;
        }

        $reached = max((int) $this->package->setup_step, $step);
        if ((int) $this->package->setup_step !== $reached) {
            $this->package->updateQuietly(['setup_step' => $reached]);
        }
    }

    private function autosave(callable $save): void
    {
        if ($this->managing || ! $this->package) {
            return;
        }

        try {
            $save();
        } catch (Throwable $e) {
            Log::warning('Package autosave skipped', ['package_id' => $this->package?->id, 'error' => $e->getMessage()]);
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
            Log::warning('Package autosave skipped', ['package_id' => $this->package?->id, 'error' => $e->getMessage()]);
        }
    }

    private function autosaveMedia(): void
    {
        $this->autosave(fn () => $this->persistMedia());
    }

    private function loadPackage(Package $package): void
    {
        $package->load(['components']);
        $this->package = $package;

        $this->fill([
            'name' => (string) $package->name,
            'slug' => (string) $package->slug,
            'short_description' => (string) $package->short_description,
            'full_description' => (string) $package->full_description,
            'destination_id' => $package->destination_id,
            'extra_destinations' => $package->extraDestinationIds(),
            'duration_days' => max(1, (int) $package->duration_days ?: 1),
            'components' => $package->components->map(fn (PackageComponent $c) => [
                'day' => (int) $c->day,
                'kind' => $c->kind,
                'type' => $c->componentable_type,
                'listing_id' => $c->componentable_id,
                'option_id' => $c->activity_option_id,
                'nights' => $c->nights,
                'quantity' => (int) $c->quantity ?: 1,
                'title' => in_array($c->kind, ['stay', 'experience'], true) ? '' : (string) $c->title,
                'description' => (string) $c->description,
                'price' => $c->price,
                'currency' => $c->currency,
            ])->values()->all(),
            'inclusions' => array_values((array) ($package->inclusions ?? [])) ?: [''],
            'exclusions' => array_values((array) ($package->exclusions ?? [])) ?: [''],
            'cover_path' => $package->cover_image,
            'cover_video_path' => $package->cover_video,
            'gallery_paths' => array_values((array) ($package->gallery ?? [])),
            'currency' => $package->getCurrency(),
            'markup_type' => $package->markup_type ?: 'percent',
            'markup_value' => (float) $package->markup_value,
            'min_guests' => (int) $package->min_guests ?: 1,
            'max_guests' => (int) $package->max_guests ?: 2,
            'sampleGuests' => max(2, (int) $package->min_guests),
            'sampleDate' => today()->addDays((int) config('packages.default_lead_days', 30))->toDateString(),
        ]);
    }

    private function countryCodeFor(?string $country): ?string
    {
        return blank($country) ? null : \App\Shared\Support\Countries::codeFor($country);
    }
}
