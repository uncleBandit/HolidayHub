<?php

namespace App\Modules\Booking\Domain\Events;

use App\Modules\Booking\Domain\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    /**
     * Broadcast channel(s) this event should go to.
     */
    public function broadcastOn(): array
    {
        // Private channel per owner. bookings stores the owner as guest_id, not
        // user_id, so the guest's owning user is what gets the channel name.
        return [
            new Channel('bookings'),
            new Channel('user.'.$this->booking->guest?->user_id),
        ];
    }

    /**
     * Optional alias for event name on frontend.
     */
    public function broadcastAs(): string
    {
        return 'booking.created';
    }

    /**
     * Data exposed to clients (sanitized).
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->booking->id,
            'guest_id' => $this->booking->guest_id,
            'status' => $this->booking->status,
            'check_in' => $this->booking->check_in_date?->toDateString(),
            'check_out' => $this->booking->check_out_date?->toDateString(),
            'total' => $this->booking->total_amount,
            'created_at' => $this->booking->created_at?->toDateTimeString(),
        ];
    }
}
