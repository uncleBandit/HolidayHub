<?php

namespace App\Livewire\Booking;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class BookingShow extends Component
{
    public Booking $booking;


    // In App\Livewire\Booking\BookingShow
    public function mount(Booking $booking): void
    {
        // Eager load the guest relationship to access its user_id
        $booking->load('guest');

        // Check if the booking's guest's user_id matches the authenticated user's id
        if ($booking->guest->user->id !== Auth::id()) {
                abort(403);
            }

        // Now that ownership is verified, eager load other relations.
        $this->booking = $booking->load(['bookable', 'offer', 'destination']);
    }

    public function rebook(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('bookables.show', [
            'id'       => $this->booking->bookable_id,
            'checkIn'  => $this->booking->check_in_date->format('Y-m-d'),
            'checkOut' => $this->booking->check_out_date->format('Y-m-d'),
            'adults'   => $this->booking->guests_adults,
            'children' => $this->booking->guests_children,
        ]);
    }

    public function render()
    {
        return view('livewire.booking.booking-show', [
            'booking' => $this->booking,
        ]);
    }
}
