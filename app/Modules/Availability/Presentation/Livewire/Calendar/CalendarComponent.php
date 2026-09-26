<?php

namespace App\Modules\Availability\Presentation\Livewire\Calendar;

use App\Modules\Availability\Application\Services\AvailabilityEngine;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\NightlyPricer;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CalendarComponent extends Component
{
    /**
     * @var Bookable|\Illuminate\Database\Eloquent\Model|null
     */
    public ?Bookable $bookable = null;

    public ?string $checkin = null;

    public ?string $checkout = null;

    public array $availability = [];

    public string $bookableType;

    public ?int $bookableId;

    public ?Carbon $checkinDate = null;

    public ?Carbon $checkoutDate = null;

    public ?Carbon $currentMonth = null;

    public array $calendarDays = [];

    public int $minStay = 2;

    public int $maxStay = 14;

    private NightlyPricer $pricer;

    private AvailabilityEngine $availabilityEngine;

    /**
     * Livewire does not construct components, so this is where the pricer and the
     * availability engine arrive. Non-public typed properties are never
     * serialized, so they are resolved fresh on every hydration.
     */
    public function boot(): void
    {
        $this->pricer = app(NightlyPricer::class);
        $this->availabilityEngine = app(AvailabilityEngine::class);
    }

    public function mount(Bookable $bookable): void
    {
        if (! $bookable->exists) {
            Log::error('CalendarComponent mounted without persisted bookable', [
                'class' => get_class($bookable),
            ]);

            return;
        }
        $this->bookableType = $bookable->getMorphClass();
        $this->bookableId = $bookable->id;
        $this->bookable = $bookable;

        // Initialize and load the availability data just once.
        $this->loadAvailability();

        // Set the calendar to the current month and load its data.
        $this->currentMonth = $this->currentMonth ?? Carbon::now()->startOfMonth();
        $this->loadCalendarData();
    }

    /**
     * Loads availability data for the calendar.
     *
     * The previous implementation re-derived the nightly price itself, matching
     * seasonal rates and offers on a `date_key` column that does not exist, so it
     * always fell back to the base price — and then summed those per-day prices
     * in book(), quietly disagreeing with the checkout total. It also queried
     * `check_in` and `check_out` on bookings, which have no such columns.
     *
     * Both worries now belong to their owners: the AvailabilityEngine expands the
     * date-range schedule and blocking bookings, and the bookable asks the shared
     * NightlyPricer for its price per night, the same calculator the booking flow
     * uses — so what the calendar shows is what the booking costs.
     */
    public function loadAvailability(): void
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonths(6);

        $days = $this->availabilityEngine->forBookable($this->bookable, $startDate, $endDate);

        $this->availability = [];

        foreach ($days as $dateString => $day) {
            $price = $day['price'] ?? null;

            // An unconfigured date has no recorded price; ask the canonical
            // calculator instead of showing zero or a stale base price.
            if ($price === null && $day['source'] === 'unconfigured') {
                $price = $this->pricer->nightlyPrice($this->bookable, Carbon::parse($dateString));
            }

            $this->availability[$dateString] = [
                'available' => $day['available'],
                'price' => (float) $price,
            ];
        }
    }

    public function book(): void
    {
        $this->validate([
            'checkin' => 'required|date|after_or_equal:today',
            'checkout' => 'required|date|after:checkin',
        ]);

        $start = Carbon::parse($this->checkin);
        $end = Carbon::parse($this->checkout);
        $nights = $start->diffInDays($end);

        if ($nights < $this->minStay || $nights > $this->maxStay) {
            $this->addError('stay', "Stay must be between {$this->minStay} and {$this->maxStay} nights.");

            return;
        }

        // Check availability + total price
        $total = 0;
        $unavailable = [];

        foreach (CarbonPeriod::create($start, $end->subDay()) as $date) {
            $dateStr = $date->toDateString();
            $day = $this->availability[$dateStr] ?? null;

            if (! $day || ! $day['available']) {
                $unavailable[] = $dateStr;
            } else {
                $total += $day['price'];
            }
        }

        if (! empty($unavailable)) {
            $this->addError('dates', 'Unavailable dates: '.implode(', ', $unavailable));

            return;
        }

        // All checks passed -> trigger booking flow
        $this->dispatch('openBookingModal', [
            'bookable_type' => $this->bookable->getMorphClass(),
            'bookable_id' => $this->bookable->id,
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'nights' => $nights,
            'total' => $total,
        ]);

        Log::info('CalendarComponent::book() fired', [
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
        ]);

        session()->flash('success', "Booking request for {$nights} nights submitted! Total: \${$total}");
    }

    public function loadCalendarData(): void
    {
        $this->calendarDays = [];
        $start = $this->currentMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $this->currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dateString = $date->toDateString();
            $dayData = $this->availability[$dateString] ?? ['available' => false, 'price' => 0];

            $class = 'day-cell';
            $isBookable = true;

            if (! $date->isSameMonth($this->currentMonth) || $date->isPast()) {
                $class .= ' disabled-month';
                $isBookable = false;
            } elseif (! $dayData['available']) {
                $class .= ' unavailable';
                $isBookable = false;
            } else {
                $class .= ' available';
            }

            if ($this->checkinDate && $date->equalTo($this->checkinDate)) {
                $class .= ' selected';
            }
            if ($this->checkoutDate && $date->equalTo($this->checkoutDate)) {
                $class .= ' selected';
            }
            if ($this->checkinDate && $this->checkoutDate && $date->between($this->checkinDate, $this->checkoutDate)) {
                $class .= ' selected-range';
            }

            if ($date->isToday()) {
                $class .= ' today';
            }

            $this->calendarDays[] = [
                'day_number' => $date->day,
                'date_string' => $dateString,
                'is_bookable' => $isBookable,
                'is_available' => $dayData['available'],
                'price' => $dayData['price'],
                'class' => $class,
            ];
        }
    }

    public function selectDate(string $dateString): void
    {
        $date = Carbon::parse($dateString);

        if (! $this->checkinDate || ($this->checkinDate && $this->checkoutDate)) {
            $this->checkinDate = $date;
            $this->checkoutDate = null;
        } elseif ($date->gt($this->checkinDate)) {
            $this->checkoutDate = $date;
        } else {
            $this->checkinDate = $date;
            $this->checkoutDate = null;
        }

        $this->checkin = $this->checkinDate ? $this->checkinDate->toDateString() : null;
        $this->checkout = $this->checkoutDate ? $this->checkoutDate->toDateString() : null;

        $this->loadCalendarData();

        // Dispatch the event after the state has been updated
        if ($this->checkin && $this->checkout) {
            $this->dispatch('staySelected', checkin: $this->checkin, checkout: $this->checkout);
        }
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->currentMonth
            ? $this->currentMonth->copy()->subMonth()->startOfMonth()
            : Carbon::now()->startOfMonth();
        $this->loadCalendarData();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->currentMonth
            ? $this->currentMonth->copy()->addMonth()->startOfMonth()
            : Carbon::now()->startOfMonth();
        $this->loadCalendarData();
    }

    public function render()
    {
        return view('livewire.calendar.calendar-component');
    }
}
