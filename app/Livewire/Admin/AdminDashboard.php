<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Component
{
    use WithPagination;

    public string $filterRole = 'all'; // all, agent, provider, guest
    public string $status = 'all'; // all, pending, verified, suspended
    public string $search = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 12;

    protected $queryString = ['filterRole','status','search','sortField','sortDirection','page'];

    protected $listeners = [
        'confirmVerification' => 'verifyUser',
    ];

    public function mount()
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleSort(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function getPendingCountProperty(): int
    {
        return User::pendingVerification()->count();
    }

    public function getStatsProperty(): array
    {
        return Cache::remember('admin.dashboard.stats', 60, function () {
            return [
                'users_total' => User::count(),
                'agents_total' => User::role('agent')->count(),
                'providers_total' => User::role('provider')->count(),
                'bookings_30d' => Booking::where('created_at', '>=', now()->subDays(30))->count(),
                'hotels_total' => Hotel::count(),
            ];
        });
    }

    protected function userQuery()
    {
        return User::query()
            ->when($this->filterRole !== 'all' && $this->filterRole, fn($q) => $q->where('role', $this->filterRole))
            ->when($this->status === 'pending', fn($q) => $q->whereNull('verified_at'))
            ->when($this->status === 'verified', fn($q) => $q->whereNotNull('verified_at')->where('is_active', true))
            ->when($this->status === 'suspended', fn($q) => $q->where('is_active', false))
            ->when($this->search, fn($q) => $q->where(fn($sub) =>
                $sub->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function verifyUser(int $userId, ?string $note = null)
    {
        Gate::authorize('admin.manage-users');

        $user = User::findOrFail($userId);

        if ($user->verified_at) {
            $this->dispatch('toast', ['type' => 'info', 'message' => 'User already verified.']);
            return;
        }

        $user->markVerified($note);

        AuditLog::create([
            'admin_id' => auth()->id(),
            'action' => 'verify_user',
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'meta' => ['note' => $note],
        ]);

        // Invalidate caches
        Cache::forget('admin.dashboard.stats');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'User verified successfully.']);
        $this->emitSelf('refresh');
    }

    public function suspendUser(int $userId, string $reason = null)
    {
        Gate::authorize('admin.manage-users');

        $user = User::findOrFail($userId);
        $user->suspend($reason);

        AuditLog::create([
            'admin_id' => auth()->id(),
            'action' => 'suspend_user',
            'subject_type' => get_class($user),
            'subject_id' => $user->id,
            'meta' => ['reason' => $reason],
        ]);

        Cache::forget('admin.dashboard.stats');

        $this->dispatch('toast', ['type' => 'warning', 'message' => 'User suspended.']);
        $this->emitSelf('refresh');
    }

    public function resetFilters()
    {
        $this->filterRole = 'all';
        $this->status = 'all';
        $this->search = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function render()
    {
        $users = $this->userQuery()->paginate($this->perPage);

        return view('livewire.admin.admin-dashboard', [
            'users' => $users,
            'stats' => $this->stats,
            'pendingCount' => $this->pendingCount,
        ]);
    }
}
