<?php

namespace App\Livewire\Agent;

use App\Models\Booking;
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

    protected $queryString = ['search', 'status', 'dateFrom', 'dateTo'];

    public function updated($property)
    {
        if (in_array($property, ['search', 'status', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $bookings = Booking::with(['package', 'customer'])
            ->when($this->search, fn($q) =>
                $q->whereHas('customer', fn($c) =>
                    $c->where('name', 'like', "%{$this->search}%")
                )->orWhereHas('package', fn($p) =>
                    $p->where('title', 'like', "%{$this->search}%")
                )
            )
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at', 'desc')
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
