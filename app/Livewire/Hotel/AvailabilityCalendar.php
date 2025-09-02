<?php

namespace App\Livewire\Hotel;

use App\Models\Hotel;
use App\Models\Booking;
use Carbon\Carbon;
use Livewire\Component;

class AvailabilityCalendar extends Component
{
    public Hotel $hotel;

    public $startDate;
    public $endDate;
    public $availability = [];

    protected $listeners = [
        'dateRangeSelected' => 'updateDateRange',
    ];

    public function mount(Hotel $hotel)
    {
        $this->hotel = $hotel;
        $this->startDate = Carbon::today();
        $this->endDate = Carbon::today()->addMonths(3);

        $this->loadAvailability();
    }

    public function updateDateRange($start, $end)
    {
        $this->startDate = Carbon::parse($start);
        $this->endDate = Carbon::parse($end);

        $this->loadAvailability();
    }

    protected function loadAvailability()
    {
        $bookedDates = Booking::query()
            ->where('hotel_id', $this->hotel->id)
            ->whereBetween('check_in', [$this->startDate, $this->endDate])
            ->orWhereBetween('check_out', [$this->startDate, $this->endDate])
            ->pluck('check_in', 'check_out')
            ->toArray();

        $this->availability = collect(
            Carbon::parse($this->startDate)
                ->daysUntil($this->endDate)
        )->mapWithKeys(function ($date) use ($bookedDates) {
            $booked = collect($bookedDates)->some(function ($checkIn, $checkOut) use ($date) {
                return $date->between($checkIn, $checkOut);
            });

            return [
                $date->toDateString() => [
                    'date' => $date->toDateString(),
                    'is_available' => ! $booked,
                    'price' => $this->hotel->base_price, // extend later with dynamic pricing
                ],
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.hotel.availability-calendar', [
            'availability' => $this->availability,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }
}
