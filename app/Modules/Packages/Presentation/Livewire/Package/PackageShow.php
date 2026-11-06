<?php

namespace App\Modules\Packages\Presentation\Livewire\Package;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Booking\Presentation\Support\TripPresenter;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Packages\Application\Services\PackageBooker;
use App\Modules\Packages\Application\Services\PackagePricing;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Domain\Models\PackageComponent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A package as guests see it: the cover film and gallery, the journey reel,
 * the itinerary day by day (each card links to the real listing), what's
 * included, and a booking card that quotes the whole journey live for a
 * start date and party size, then books it all at once (PackageBooker).
 *
 * Only live packages are public; the agent and admins can preview others.
 */
#[Layout('layouts.discovery')]
class PackageShow extends Component
{
    public Package $package;

    public string $startDate = '';

    public int $guests = 2;

    /** @var array<int, array{date: string, total: float, per_person: float}> */
    public array $departures = [];

    public function mount(Package $package): void
    {
        abort_unless(Gate::allows('view', $package), 404);

        $this->package = $package;
        $this->guests = max((int) $package->min_guests, min(2, (int) $package->max_guests ?: 2));

        $this->departures = app(PackagePricing::class)->nextDepartures($this->freshPackage(), $this->guests, 4);
        $this->startDate = $this->departures[0]['date'] ?? today()->addDays((int) config('packages.default_lead_days', 30))->toDateString();
    }

    public function updatedGuests(): void
    {
        $this->guests = max(1, min(50, (int) $this->guests));
    }

    public function setGuests(int $guests): void
    {
        $this->guests = max(max(1, (int) $this->package->min_guests), min((int) $this->package->max_guests ?: 50, $guests));
    }

    public function pickDate(string $date): void
    {
        $this->startDate = Carbon::parse($date)->toDateString();
    }

    /** The shared gallery hero has a save button; journeys can't be wishlisted yet. */
    public function toggleWishlist(): void
    {
        $this->dispatch('toast', type: 'info', message: 'Saving journeys is coming soon.');
    }

    public function book()
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'));
        }

        $this->validate([
            'startDate' => ['required', 'date', 'after:today'],
            'guests' => ['required', 'integer', 'min:'.max(1, (int) $this->package->min_guests), 'max:'.max(1, (int) $this->package->max_guests)],
        ], [
            'startDate.after' => 'Choose a start date from tomorrow onwards.',
            'guests.min' => 'This journey is for at least '.(int) $this->package->min_guests.' guests.',
            'guests.max' => 'This journey takes up to '.(int) $this->package->max_guests.' guests.',
        ]);

        try {
            $booking = app(PackageBooker::class)->book($this->package, Auth::user(), $this->startDate, $this->guests);
        } catch (ValidationException $e) {
            $this->addError('booking', collect($e->errors())->flatten()->first());

            return null;
        }

        session()->flash('success', 'Your journey is booked.');

        return redirect()->route('booking-confirmation', $booking);
    }

    public function render()
    {
        $package = $this->freshPackage();
        $pricing = app(PackagePricing::class);
        $quote = null;

        try {
            $quote = $this->startDate ? $pricing->quote($package, $this->startDate, max(1, $this->guests)) : null;
        } catch (\Throwable $e) {
            report($e);
        }

        $destinations = Destination::query()
            ->whereIn('id', array_filter(array_merge([$package->destination_id], $package->extraDestinationIds())) ?: [0])
            ->get(['id', 'name', 'slug', 'country']);

        return view('livewire.package.package-show', [
            'p' => $package,
            'quote' => $quote,
            'itinerary' => $this->itinerary($package),
            'destinations' => $destinations,
            'included' => $this->included($package),
            'preview' => ! $package->isPublished(),
        ])->title($package->name.' · HolidayHub');
    }

    private function freshPackage(): Package
    {
        $package = Package::query()->whereKey($this->package->id)->firstOrFail();
        $package->load([
            'agent',
            'destination',
            'scenes',
            ...app(PackagePricing::class)->componentRelations(),
        ]);

        // Pictures and destinations for the itinerary cards.
        $package->components->loadMorph('componentable', [
            RoomType::class => ['hotel.accommodation.destination'],
            Villa::class => ['accommodation.destination'],
            BedAndBreakfast::class => ['accommodation.destination'],
            Activity::class => ['destination'],
        ]);

        return $package;
    }

    /** The journey grouped by day, each part with its picture, place and link. */
    private function itinerary(Package $package): array
    {
        $days = max(1, (int) $package->duration_days, (int) $package->components->max('day'));

        return collect(range(1, $days))->map(fn (int $day) => [
            'day' => $day,
            'parts' => $package->components->where('day', $day)->values()->map(fn (PackageComponent $c) => $this->part($c))->all(),
        ])->all();
    }

    private function part(PackageComponent $component): array
    {
        $listing = $component->componentable;
        $owner = $listing instanceof RoomType ? $listing->hotel : $listing;

        return [
            'kind' => $component->kind,
            'label' => match (true) {
                $listing instanceof RoomType => 'Hotel',
                $listing instanceof Villa => 'Villa',
                $listing instanceof BedAndBreakfast => 'B&B',
                $listing instanceof Activity => 'Experience',
                default => PackageComponent::KINDS[$component->kind] ?? 'Extra',
            },
            'title' => $listing ? ($owner?->name ?? $component->title) : ($component->title ?: PackageComponent::KINDS[$component->kind]),
            'detail' => match (true) {
                $listing instanceof RoomType => $listing->name,
                $listing instanceof Activity => $component->activityOption?->name,
                default => null,
            },
            'nights' => $component->kind === 'stay' ? max(1, (int) $component->nights) : null,
            'text' => $component->description ?: ($listing ? Str::limit(strip_tags((string) ($listing instanceof Activity ? ($listing->short_description ?: $listing->description) : ($owner?->description ?? ''))), 150) : null),
            'image' => $listing ? TripPresenter::image($owner, $listing instanceof Activity ? 'activity' : 'hotel', 600) : null,
            'place' => $listing instanceof Activity ? $listing->destination?->name : $owner?->accommodation?->destination?->name,
            'url' => match (true) {
                $listing instanceof RoomType && $owner => route('hotel-show', $owner),
                $listing instanceof Villa => route('villa.show', $listing),
                $listing instanceof BedAndBreakfast => route('bedandbreakfast.show', $listing),
                $listing instanceof Activity => route('activity.show', $listing),
                default => null,
            },
            'minutes' => $listing instanceof Activity ? (int) ($component->activityOption?->duration_minutes ?: $listing->duration_minutes) : null,
        ];
    }

    /** "What's included": the stays, experiences and extras, then the agent's own lines. */
    private function included(Package $package): array
    {
        $components = $package->components;
        $nights = (int) $components->where('kind', 'stay')->sum(fn ($c) => max(1, (int) $c->nights));
        $stays = $components->where('kind', 'stay')->count();
        $experiences = $components->where('kind', 'experience');
        $transfers = $components->where('kind', 'transfer');

        $lines = collect();
        if ($nights) {
            $lines->push($nights.' '.Str::plural('night', $nights).' in '.$stays.' '.Str::plural('stay', $stays).', booked with each host');
        }
        foreach ($experiences as $experience) {
            $lines->push($experience->componentable?->name ?? $experience->title);
        }
        foreach ($transfers as $transfer) {
            $lines->push($transfer->title ?: 'Transfer');
        }

        return [
            'in' => $lines->concat((array) ($package->inclusions ?? []))->filter()->unique()->values()->all(),
            'out' => collect((array) ($package->exclusions ?? []))->filter()->values()->all(),
        ];
    }
}
