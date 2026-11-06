<?php

namespace App\Modules\Booking\Presentation\Livewire\Booking;

use App\Modules\Booking\Application\Services\BookingManager;
use App\Modules\Booking\Application\Services\PolicyEngine;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Booking\Domain\ValueObjects\PolicyResult;
use App\Modules\Booking\Presentation\Support\TripPresenter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BookingShow extends Component
{
    public Booking $booking;

    /** Optional note the guest leaves when cancelling. */
    public string $cancelReason = '';

    public function mount(Booking $booking): void
    {
        $booking->load('guest');

        if ($booking->guest?->user_id !== Auth::id()) {
            abort(403);
        }

        $this->booking = $booking->load(['bookable', 'offer', 'destination', 'activitySession', 'packageBooking']);
    }

    /**
     * Whether the guest can cancel now and what they would get back, from the
     * listing's own cancellation policy.
     */
    #[Computed]
    public function cancellation(): PolicyResult
    {
        return app(PolicyEngine::class)->canCancel($this->booking);
    }

    public function cancel(BookingManager $bookings): void
    {
        $this->validate(['cancelReason' => 'nullable|string|max:500']);

        try {
            $refund = $bookings->cancel($this->booking->id, (int) Auth::id(), trim($this->cancelReason) ?: null);
        } catch (\DomainException|\RuntimeException $e) {
            $this->addError('cancel', $e->getMessage());

            return;
        }

        $this->booking->refresh()->load(['bookable', 'offer', 'destination', 'activitySession', 'packageBooking']);
        unset($this->cancellation);
        $this->cancelReason = '';

        session()->flash('message', 'Your booking is cancelled. '.$refund['summary']);
        $this->dispatch('booking-cancelled');
    }

    /** Book the same listing again, from its own page. */
    public function rebook()
    {
        $url = TripPresenter::url($this->booking->bookable);

        return $url ? redirect()->to($url) : redirect()->route('bookings');
    }

    public function render()
    {
        return view('livewire.booking.booking-show', [
            'booking' => $this->booking,
        ]);
    }
}
