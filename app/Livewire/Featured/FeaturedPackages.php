<?php

namespace App\Livewire\Featured;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Package;
use Illuminate\Support\Facades\Cache;

class FeaturedPackages extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public int $perPage = 6;
    public string $search = '';
    public ?int $destination = null;
    public ?float $minPrice = null;
    public ?float $maxPrice = null;
    public string $sortBy = 'latest'; // latest | price_asc | price_desc
    public int $page = 1;
    public bool $showResults = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'destination' => ['except' => null],
        'minPrice' => ['except' => null],
        'maxPrice' => ['except' => null],
        'sortBy' => ['except' => 'latest'],
        'page' => ['except' => 1],
    ];

    /**
     * Reset page on filter/search change
     */
    public function updating($property): void
    {
        if (in_array($property, ['search', 'destination', 'minPrice', 'maxPrice', 'sortBy', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->showResults = !empty($this->search);
    }

    /**
     * Build the packages query
     */
    protected function buildQuery()
    {
        return Package::query()
            ->with(['destination']) // eager load to avoid N+1
            ->when(
                empty($this->search) && !$this->destination && !$this->minPrice && !$this->maxPrice,
                fn($q) => $q->where('is_featured', true)
            )
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('title', 'like', "%{$this->search}%")
                        ->orWhere('short_description', 'like', "%{$this->search}%")
                        ->orWhere('long_description', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                });
            })
            ->when($this->destination, fn($q) => $q->where('destination_id', $this->destination))
            ->when($this->minPrice, fn($q) => $q->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn($q) => $q->where('price', '<=', $this->maxPrice))
            ->orderByDesc('is_featured') // always prioritize featured
            ->tap(function ($q) {
                match ($this->sortBy) {
                    'price_asc' => $q->orderBy('price', 'asc'),
                    'price_desc' => $q->orderBy('price', 'desc'),
                    default => $q->orderBy('updated_at', 'desc'),
                };
            });
    }

    /**
     * Render the component
     */
    public function render()
    {
        $cacheKey = "featured_packages_" . md5(json_encode([
            'search' => $this->search,
            'destination' => $this->destination,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'sortBy' => $this->sortBy,
            'perPage' => $this->perPage,
            'page' => $this->page,
        ]));

        $packages = Cache::remember(
        $cacheKey,
        now()->addMinutes(10),
        fn() => $this->buildQuery()->paginate($this->perPage)
        );


        if (empty($this->search)) {
            $this->showResults = false;
        }

        return view('livewire.featured.featured-packages', [
            'packages' => $packages,
        ]);
    }
}
