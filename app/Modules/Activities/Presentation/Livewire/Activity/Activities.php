<?php

namespace App\Modules\Activities\Presentation\Livewire\Activity;

use App\Modules\Activities\Application\Services\ActivitySearchQuery;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Destinations\Domain\Models\Destination;
use Livewire\Component;
use Livewire\WithPagination;

class Activities extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $destinationId = null;

    public ?int $categoryId = null;

    public string $sortBy = 'rating';

    protected string $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'destinationId' => ['except' => null],
        'categoryId' => ['except' => null],
        'sortBy' => ['except' => 'rating'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDestinationId(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function render(ActivitySearchQuery $searchQuery)
    {
        $sort = match ($this->sortBy) {
            'newest' => 'latest',
            'price' => 'price_low_high',
            default => 'rating',
        };

        return view('livewire.activities', [
            'activities' => $searchQuery->build([
                'search' => $this->search,
                'destination_id' => $this->destinationId,
                'category_id' => $this->categoryId,
                'sort' => $sort,
            ])->paginate(12),
            'destinations' => Destination::query()->orderBy('name')->get(['id', 'name']),
            'categories' => ActivityCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
