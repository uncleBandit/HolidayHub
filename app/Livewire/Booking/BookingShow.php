<?php

namespace App\Livewire\Booking;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class BookingShow extends Component
{
    use WithPagination;

    public string $status = 'all'; // all | upcoming | past | cancelled
    public string $search = '';

    protected $queryString = ['status', 'search', 'page'];

    public function updating($field)
    {
        if (in_array($field, ['status', 'search'])) {
            $this->resetPage();
        }
    }

    public function getBookingsProperty()
    {
        return Booking::with(['bookable', 'review'])
            ->where('user_id', Auth::id())
            ->when($this->status !== 'all', function ($query) {
                match ($this->status) {
                    'upcoming' => $query->where('check_in', '>=', now()),
                    'past'     => $query->where('check_out', '<', now()),
                    'cancelled'=> $query->where('status', 'cancelled'),
                };
            })
            ->when($this->search, fn ($query) =>
                $query->whereHas('bookable', fn ($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                )
            )
            ->latest('check_in')
            ->paginate(10);
    }

    public function rebook(int $bookingId)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($bookingId);

        return redirect()->route('bookables.show', [
            'id'       => $booking->bookable_id,
            'checkIn'  => $booking->check_in->format('Y-m-d'),
            'checkOut' => $booking->check_out->format('Y-m-d'),
            'guests'   => $booking->guests,
        ]);
    }

    public function render()
    {
        return view('livewire.booking.booking-show', [
            'bookings' => $this->bookings,
        ]);
    }
}
