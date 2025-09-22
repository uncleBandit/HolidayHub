<?php

namespace App\Livewire\Booking;

use App\Models\Booking;
use App\Models\Package;
use Livewire\Component;

class BookPackage extends Component
{
    public $packageId;
    public $startDate;
    public $endDate;
    public $guests = 1;
    public $includeMeals = false;
    public $roomUpgrade = false;
    public $extraNight = false;
    public $totalPrice = 0;

    protected $listeners = ['book-package' => 'createBooking'];

    public function mount($packageId)
    {
        $this->packageId = $packageId;
    }

    public function calculateTotal(): float
    {
        $package = Package::findOrFail($this->packageId);
        $basePrice = $package->final_price * $this->guests;

        if ($this->includeMeals) {
            $basePrice += 50 * $this->guests;
        }
        if ($this->roomUpgrade) {
            $basePrice += 120 * $this->guests;
        }
        if ($this->extraNight) {
            $basePrice += ($package->final_price * 0.8) * $this->guests;
        }

        return $this->totalPrice = $basePrice;
    }

    public function createBooking($data)
    {
        $this->startDate = $data['startDate'] ?? null;
        $this->endDate   = $data['endDate'] ?? null;
        $this->guests    = $data['guests'] ?? 1;
        $this->includeMeals = $data['meals'] ?? false;
        $this->roomUpgrade  = $data['upgrade'] ?? false;
        $this->extraNight   = $data['extraNight'] ?? false;

        $total = $this->calculateTotal();

        $booking = Booking::create([
            'package_id'    => $this->packageId,
            'user_id'       => auth()->id(),
            'start_date'    => $this->startDate,
            'end_date'      => $this->endDate,
            'guests'        => $this->guests,
            'include_meals' => $this->includeMeals,
            'room_upgrade'  => $this->roomUpgrade,
            'extra_night'   => $this->extraNight,
            'total_price'   => $total,
            'status'        => 'pending',
        ]);

        return redirect()->route('checkout.show', $booking);
    }

    public function render()
    {
        return view('livewire.booking.book-package');
    }
}
