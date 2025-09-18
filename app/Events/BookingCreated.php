<?php

namespace App\Events;

use App\Models\Booking;
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
        // Example: send to private channel per user
        return [
            new Channel('bookings'),
            new Channel("user.{$this->booking->user_id}"),
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
            'id'         => $this->booking->id,
            'user_id'    => $this->booking->user_id,
            'status'     => $this->booking->status,
            'check_in'   => $this->booking->check_in->toDateString(),
            'check_out'  => $this->booking->check_out->toDateString(),
            'total'      => $this->booking->total_price,
            'created_at' => $this->booking->created_at->toDateTimeString(),
        ];
    }
}
