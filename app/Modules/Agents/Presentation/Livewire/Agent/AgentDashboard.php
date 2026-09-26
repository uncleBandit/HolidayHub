<?php

namespace App\Modules\Agents\Presentation\Livewire\Agent;

use App\Modules\Booking\Domain\Models\Booking;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AgentDashboard extends Component
{
    use WithPagination;

    public $search = '';

    public $status = null;

    public $perPage = 10;

    public $dateFrom = null;

    public $dateTo = null;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    protected $queryString = ['search', 'status', 'dateFrom', 'dateTo', 'sortBy', 'sortDirection'];

    public function updated($property)
    {
        if (in_array($property, ['search', 'status', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updateStatus($bookingId, $status)
    {
        $booking = Booking::findOrFail($bookingId);
        $booking->update(['status' => $status]);

        session()->flash('message', "Booking #{$booking->id} updated to {$status}");
    }

    public function render()
    {
        $bookings = Booking::with(['bookable', 'guest'])
            ->when($this->search, fn ($q) => $q->whereHas('guest', fn ($c) => $c->where('name', 'like', "%{$this->search}%")
            )->orWhereHas('bookable', fn ($b) => $b->where('name', 'like', "%{$this->search}%")
                ->orWhere('title', 'like', "%{$this->search}%")
            )
            )
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        $stats = [
            'totalBookings' => Booking::count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'todayRevenue' => Booking::whereDate('created_at', Carbon::today())->sum('total_price'),
        ];

        return view('livewire.agent.dashboard', compact('bookings', 'stats'));
    }
}
