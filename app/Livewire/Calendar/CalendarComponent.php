<?php

namespace App\Livewire\Calendar;

use Livewire\Component;
use App\Contracts\Interface\Bookable;
use App\Services\AvailabilityEngine;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalendarComponent extends Component
{
    /**
    * @var \App\Models\Bookable|\App\Contracts\Bookable
     */
    public Bookable $bookable;


    public ?string $checkin = null;
    public ?string $checkout = null;
     public array $availability = [];

    public int $minStay = 2;
    public int $maxStay = 14;

    public function mount(Bookable $bookable): void
    {
        $this->bookable = $bookable;
        $this->loadAvailability();
    }

    public function loadAvailability(): void
    {
        $this->availability = app(AvailabilityEngine::class)
            ->forBookable($this->bookable, now(), now()->addMonths(6));
    }

    public function book(): void
    {
        $this->validate([
            'checkin'  => 'required|date|after_or_equal:today',
            'checkout' => 'required|date|after:checkin',
        ]);

        $start = Carbon::parse($this->checkin);
        $end   = Carbon::parse($this->checkout);
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

        // All checks passed → trigger booking flow
        $this->dispatch('bookingInitiated', [
            'bookable_type' => get_class($this->bookable),
            'bookable_id'   => $this->bookable->id,
            'checkin'       => $this->checkin,
            'checkout'      => $this->checkout,
            'nights'        => $nights,
            'total'         => $total,
        ]);

        session()->flash('success', "Booking request for {$nights} nights submitted! Total: \${$total}");
    }

    public function render()
    {
        return view('livewire.calendar-component');
    }
}
