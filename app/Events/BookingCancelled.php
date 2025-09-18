<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?string $reason = null // Optional: cancellation reason
    ) {}

    /**
     * Broadcast channel(s).
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('bookings'),
            new Channel("user.{$this->booking->user_id}"),
        ];
    }

    /**
     * Event alias for frontend.
     */
    public function broadcastAs(): string
    {
        return 'booking.cancelled';
    }

    /**
     * Payload for broadcasting.
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
            'reason'     => $this->reason,
            'cancelled_at' => now()->toDateTimeString(),
        ];
    }
}
