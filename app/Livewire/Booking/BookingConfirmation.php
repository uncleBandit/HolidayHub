<?php

namespace App\Livewire\Booking;

use Livewire\Component;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingConfirmation extends Component
{
    public Booking $booking;

    /**
     * Mounts the component and performs ownership check.
     */
    public function mount(Booking $booking): void
    {
        // Eager load the guest relationship to access its user_id
        $booking->load('guest');

        // Check if the booking's guest's user_id matches the authenticated user's id
        if ($booking->guest->user_id !== Auth::id()) {
            abort(403);
        }

        // Now that ownership is verified, eager load other relations.
        $this->booking = $booking->load(['bookable', 'offer', 'destination']);
    }

    /**
     * Renders the confirmation view.
     */
    public function render()
    {
        return view('livewire.booking.booking-confirmation', [
            'booking' => $this->booking,
        ]);
    }

    /**
    * Redirects the user to the dashboard.
    */
    public function redirectToDashboard()
    {
        return $this->redirect('/dashboard', navigate: true);
    }
}
