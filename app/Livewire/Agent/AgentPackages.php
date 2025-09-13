<?php

namespace App\Livewire\Agent;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Package;
use Illuminate\Support\Facades\Auth;

class AgentPackages extends Component
{
    use WithPagination;

    // Livewire state
    public string $search = '';
    public string $statusFilter = '';
    public ?int $confirmingDeleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    // Reset pagination when filters change
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    // Confirm deletion modal
    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    // Delete package
    public function delete(int $id): void
    {
        $package = Package::where('user_id', Auth::id())->findOrFail($id);
        $package->delete();

        $this->confirmingDeleteId = null;

        session()->flash('success', 'Package deleted successfully ✅');
    }

    public function render()
    {
        $query = Package::query()
            ->where('agent_id', Auth::id())
            ->when($this->search, fn ($q) =>
                $q->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('destination', 'like', '%' . $this->search . '%');
                })
            )
            ->when($this->statusFilter, fn ($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->latest();

        $packages = $query->paginate(6);

        return view('livewire.agent.agent-packages', [
            'packages' => $packages,
        ]);
    }
}
