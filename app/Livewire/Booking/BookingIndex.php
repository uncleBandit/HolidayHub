<?php

namespace App\Livewire\Booking;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $sortField = 'check_in_date';
    public string $sortDirection = 'desc';

    protected $queryString = ['search', 'status', 'sortField', 'sortDirection'];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $bookings = Booking::query()
            ->with('bookable')
            ->whereHas('guest', function ($query) {
                        $query->where('user_id', Auth::id());
                    })
            ->when($this->search, fn($q) =>
                $q->where('confirmation_code', 'like', "%{$this->search}%")
                  ->orWhereHas('bookable', fn($q2) =>
                      $q2->where('name', 'like', "%{$this->search}%")
                  )
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.booking.booking-index', compact('bookings'));
    }
}
