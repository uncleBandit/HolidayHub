<?php

namespace App\Livewire\Calendar;

use App\Contracts\Interface\Bookable;
use Carbon\CarbonPeriod;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarComponent extends Component
{
    /**
     * @var \App\Contracts\Interface\Bookable|\Illuminate\Database\Eloquent\Model|null
     */
    public ?Bookable $bookable = null;

    public ?string $checkin = null;
    public ?string $checkout = null;
    public array $availability = [];

    public string $bookableType;
    public int $bookableId;
    public ?Carbon $checkinDate = null;
    public ?Carbon $checkoutDate = null;
    public ?Carbon $currentMonth = null;
    public array $calendarDays = [];

    public int $minStay = 2;
    public int $maxStay = 14;

    public function mount(Bookable $bookable): void
    {
        $this->bookableType = $bookable::class;
        $this->bookableId = $bookable->id;
        $this->bookable = $bookable;

        // Initialize and load the availability data just once.
        $this->loadAvailability();

        // Set the calendar to the current month and load its data.
        $this->currentMonth = now()->startOfMonth();
        $this->loadCalendarData();
    }

    /**
     * Loads availability data for the calendar using eager loading.
     * This avoids the N+1 query problem that was causing the timeout.
     *
     * @return void
     */
    public function loadAvailability(): void
    {
        // Define the date range for the calendar (e.g., 6 months from today).
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonths(6);

        // Fetch all seasonal rates for the entire period in a single query.
        $seasonalRates = $this->bookable->seasonalRates()
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        // Fetch all offers for the entire period in a single query.
        $offers = $this->bookable->offers()
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        // Fetch all bookings for all rooms for the entire period in a single query.
        $bookings = $this->bookable->rooms()
            ->with(['bookings' => function ($query) use ($startDate, $endDate) {
                $query->where('check_in', '<', $endDate)
                    ->where('check_out', '>', $startDate);
            }])
            ->get();

        // Loop through the dates and build the calendar data using the eagerly loaded data.
        $period = CarbonPeriod::create($startDate, $endDate);

        $this->availability = [];
        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $price = $this->bookable->getBasePrice();
            $available = true;

            // Check for seasonal rates and offers using the pre-loaded collections.
            if ($rate = $seasonalRates->firstWhere('date_key', $dateString)) {
                $price = $rate->rate;
            } elseif ($offer = $offers->firstWhere('date_key', $dateString)) {
                $price = $price - ($price * ($offer->discount_percentage / 100));
            }

            // Check availability by iterating through the pre-loaded bookings.
            $isRoomAvailable = false;
            foreach ($bookings as $room) {
                // Check if this specific room is available on this date.
                $isRoomAvailable = $room->bookings->where('check_in', '<', $dateString)->where('check_out', '>', $dateString)->count() === 0;

                if ($isRoomAvailable) {
                    $available = true;
                    break;
                }
            }

            // Store the final data for the view.
            $this->availability[$dateString] = [
                'available' => $available,
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

            if (!$day || !$day['available']) {
                $unavailable[] = $dateStr;
            } else {
                $total += $day['price'];
            }
        }

        if (!empty($unavailable)) {
            $this->addError('dates', 'Unavailable dates: ' . implode(', ', $unavailable));
            return;
        }

        // All checks passed -> trigger booking flow
        $this->dispatch('bookingInitiated', [
            'bookable_type' => get_class($this->bookable),
            'bookable_id' => $this->bookable->id,
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'nights' => $nights,
            'total' => $total,
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

            if (!$date->isSameMonth($this->currentMonth) || $date->isPast()) {
                $class .= ' disabled-month';
                $isBookable = false;
            } else if (!$dayData['available']) {
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

        if (!$this->checkinDate || ($this->checkinDate && $this->checkoutDate)) {
            $this->checkinDate = $date;
            $this->checkoutDate = null;
        } else if ($date->gt($this->checkinDate)) {
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
        $this->currentMonth = $this->currentMonth->copy()->subMonth();
        $this->loadCalendarData();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->currentMonth->copy()->addMonth();
        $this->loadCalendarData();
    }

    public function render()
    {
        return view('livewire.calendar.calendar-component');
    }
}
