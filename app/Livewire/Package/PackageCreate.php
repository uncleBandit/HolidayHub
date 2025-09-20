<?php

namespace App\Livewire\Package;

use App\Jobs\ProcessPackageImages;
use App\Models\Agent;
use App\Models\Destination;
use App\Models\Package;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class PackageCreate extends Component
{
    use WithFileUploads, AuthorizesRequests;

    // wizard
    public int $step = 1;
    public int $maxSteps = 5;

    // core
    public $name = '';
    public $slug = '';
    public $short_description = '';
    public $full_description = '';
    public $destination_id = null;
    public $agent_id = null;

    // pricing
    public $base_price = null;
    public $discount_price = null;
    public $currency = 'USD';
    public $duration_days = null;
    public $duration_nights = null;

    // structured arrays
    public $inclusions = [];
    public $exclusions = [];
    // itinerary: array of ['day' => 1, 'title' => '...', 'description' => '...']
    public $itinerary = [];

    // media
    public $cover_image = null; // single upload
    public $gallery = []; // multiple uploads (temporary)

    // availability / flags
    public $available_from = null;
    public $available_to = null;
    public $is_featured = false;
    public $active = false; // default to draft unless published

    // helper: typeahead search
    public $searchDestination = '';

    // draft id when autosaving
    public ?int $draftId = null;

    public string $currencySymbol = '$';


    // component mount: optionally accept draft id
    public function mount(?int $draftId = null)
    {
        // Authorization: ensure the user can create packages
        $user = Auth::user();

        // ensure the user is logged in and has an agent
        if (! $user || ! $user->agent) {
            abort(403, 'You must be logged in as an agent to create a package.');
        }

        $this->agent_id = $user->agent->id;
        $this->authorize('create', Package::class);
        $this->agent_id = Auth::user()->agent?->id;

        $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'KES' => 'KSh',
        ];

        $this->currencySymbol = $symbols[$this->currency] ?? '$';

        $this->draftId = $draftId;

        if ($this->draftId) {
            $this->loadDraft();
        }

        // ensure at least one itinerary day to start with
        if (empty($this->itinerary)) {
            $this->addItineraryDay();
        }
    }

    /* ------------------------
       Validation rules
       ------------------------ */
    protected function rules()
    {
        // unique rule for slug (create only). if you later accept editing, ignore id: Rule::unique(...)->ignore($this->draftId)
        return [
            // step 1
            'name' => 'required|string|max:255',
            'slug' => ['required','string','max:255', Rule::unique('packages', 'slug')->ignore($this->draftId)],
            'short_description' => 'nullable|string|max:500',
            'full_description' => 'nullable|string',

            'destination_id' => 'nullable|exists:destinations,id',

            // step 2
            'base_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:base_price',
            'currency' => ['required', Rule::in(['USD','EUR','GBP','KES'])],
            'duration_days' => 'nullable|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',

            // step 3 arrays
            'inclusions' => 'array',
            'inclusions.*' => 'string|max:255',
            'exclusions' => 'array',
            'exclusions.*' => 'string|max:255',

            'itinerary' => 'array',
            'itinerary.*.day' => 'required|integer|min:1',
            'itinerary.*.title' => 'required|string|max:255',
            'itinerary.*.description' => 'nullable|string',

            // step 4 media
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB
            'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            // step 5
            'available_from' => 'nullable|date|after_or_equal:today',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'is_featured' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /* ------------------------
       Step navigation
       ------------------------ */
    public function updatedName($value)
    {
        // auto slug on typing if slug is empty / initially same as derived
        if (empty($this->slug) || Str::slug($this->name) === $this->slug) {
            $this->slug = Str::slug($value);
        }
    }

    public function nextStep()
    {
        $this->validateStep();
        if ($this->step < $this->maxSteps) $this->step++;
        $this->dispatch('scrollToTop');
    }

    public function previousStep()
    {
        if ($this->step > 1) $this->step--;
        $this->dispatch('scrollToTop');
    }

    protected function validateStep()
    {
        $rulesMap = [
            1 => ['name','slug','short_description','full_description','destination_id','agent_id'],
            2 => ['base_price','discount_price','currency','duration_days','duration_nights'],
            3 => ['inclusions','inclusions.*','exclusions','exclusions.*','itinerary','itinerary.*.day','itinerary.*.title'],
            4 => ['cover_image','gallery.*'],
            5 => ['available_from','available_to','is_featured','active'],
        ];

        $this->validate(Arr::only($this->rules(), $rulesMap[$this->step]));
    }

    /* ------------------------
       Itinerary helpers
       ------------------------ */
    public function addItineraryDay(array $defaults = [])
    {
        $day = count($this->itinerary) + 1;
        $this->itinerary[] = array_merge([
            'day' => $day,
            'title' => '',
            'description' => '',
        ], $defaults);
    }

    public function removeItineraryDay($index)
    {
        if (isset($this->itinerary[$index])) {
            array_splice($this->itinerary, $index, 1);
            // reindex days
            foreach ($this->itinerary as $i => &$item) {
                $item['day'] = $i + 1;
            }
        }
    }

    /* ------------------------
       Draft autosave
       ------------------------ */
    public function updated($field)
    {
        // autosave throttle - use debounce on frontend to limit calls
        if (str_starts_with($field, 'name') ||
            str_starts_with($field, 'short_description') ||
            str_starts_with($field, 'full_description') ||
            str_starts_with($field, 'base_price') ||
            str_starts_with($field, 'itinerary') ||
            str_starts_with($field, 'inclusions') ||
            str_starts_with($field, 'exclusions')) {

            // small try/catch to avoid validation blocking
          if (!empty($this->name) && $this->base_price !== null && $this->currency) {
            $this->saveDraft(false);
        }
        }
    }

    /**
     * Save a lightweight draft. $complete = true runs full validation and sets active flag.
     */
    public function saveDraft(bool $complete = false)
    {
        // allow unauthorized users to be blocked
        $this->authorize('create', Package::class);
        $userAgentId = Auth::user()->agent->id;

        // minimal validation for draft
        if ($complete) $this->validate();

        // pack arrays into sensible storage
        $payload = [
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'short_description' => $this->short_description,
            'full_description' => $this->full_description,
            'destination_id' => $this->destination_id,
            'agent_id' => $userAgentId,
            'base_price' => $this->base_price,
            'discount_price' => $this->discount_price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,
            'inclusions' => $this->inclusions,
            'exclusions' => $this->exclusions,
            'itinerary' => $this->itinerary,
            'available_from' => $this->available_from,
            'available_to' => $this->available_to,
            'is_featured' => $this->is_featured,
            'active' => $complete ? $this->active : false, // only publish when fully saving
        ];

        if ($this->draftId) {
            $pkg = Package::find($this->draftId);
            if ($pkg) {
                $pkg->fill($payload);
                $pkg->save();
                $this->draftId = $pkg->id;
                return $pkg;
            }
        }

        // create new draft
        $pkg = Package::create($payload);
        $this->draftId = $pkg->id;
        return $pkg;
    }

    /* ------------------------
       Full save (publish) -- triggers background image processing
       ------------------------ */
    public function save()
    {
        // full validation
        $this->validate();

        // ensure auth
        $this->authorize('create', Package::class);

        // if we have a draft, update it, otherwise create
        $package = $this->saveDraft(true);

        // handle cover image synchronously into temporary disk, then queue processing
        if ($this->cover_image) {
            // store temporarily on disk 'public' and queue processing to e.g. resize/copy to S3
            $coverPath = $this->cover_image->store("packages/{$package->id}/cover", 'public');
            // store path on model
            $package->cover_image = $coverPath;
        }

        // handle gallery uploads
        if (!empty($this->gallery)) {
            $paths = [];
            foreach ($this->gallery as $upload) {
                $paths[] = $upload->store("packages/{$package->id}/gallery", 'public');
            }
            // we store raw paths (array) — model casts to array
            $package->gallery = $paths;
        }

        $package->active = $this->active; // allow publishing
        $package->is_featured = $this->is_featured;
        $package->save();

        // dispatch background job to process images (resize, create thumbnails, push to CDN/S3)
        if ($package->cover_image || ($package->gallery && count($package->gallery) > 0)) {
            ProcessPackageImages::dispatch($package);
        }

        session()->flash('success', 'Package saved successfully.');
        return redirect()->route('packages.index');
    }

    /* ------------------------
       Load an existing draft
       ------------------------ */
    protected function loadDraft()
    {
        $pkg = Package::find($this->draftId);
        if (! $pkg) {
            $this->draftId = null;
            return;
        }

        // map attributes safely
        $this->name = $pkg->name;
        $this->slug = $pkg->slug;
        $this->short_description = $pkg->short_description;
        $this->full_description = $pkg->full_description;
        $this->destination_id = $pkg->destination_id;
        $this->agent_id = $pkg->agent_id;
        $this->base_price = $pkg->base_price;
        $this->discount_price = $pkg->discount_price;
        $this->currency = $pkg->currency;
        $this->duration_days = $pkg->duration_days;
        $this->duration_nights = $pkg->duration_nights;
        $this->inclusions = $pkg->inclusions ?? [];
        $this->exclusions = $pkg->exclusions ?? [];
        $this->itinerary = $pkg->itinerary ?? [];
        $this->available_from = $pkg->available_from?->toDateString();
        $this->available_to = $pkg->available_to?->toDateString();
        $this->is_featured = (bool) $pkg->is_featured;
        $this->active = (bool) $pkg->active;
    }

    /* ------------------------
       Typeahead search for destinations & agents (lazy)
       ------------------------ */
    public function getDestinationsProperty()
    {
        if (strlen($this->searchDestination) < 2) {
            return Destination::orderBy('name')->limit(10)->get();
        }
        return Destination::where('name', 'like', "%{$this->searchDestination}%")
                    ->orderBy('name')->limit(10)->get();
    }

    public function updatedCurrency($value)
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'KES' => 'KSh',
        ];
        $this->currencySymbol = $symbols[$value] ?? '$';
    }

    public function addInclusion()
    {
        $this->inclusions[] = ''; // push an empty string to the array
    }

    public function removeInclusion($index)
    {
        unset($this->inclusions[$index]);
        $this->inclusions = array_values($this->inclusions); // reindex the array
    }

    public function addExclusion()
    {
        $this->exclusions[] = '';
    }

    


    public function render()
    {
        return view('livewire.package.package-create', [
            'destinations' => $this->destinations,
        ]);
    }
}
