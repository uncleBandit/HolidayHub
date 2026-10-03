<?php

namespace App\Modules\Activities\Presentation\Livewire\Activity;

use App\Modules\Activities\Application\Services\ActivitySessionBookingService;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySession;
use App\Modules\Media\Domain\Enums\MediaAssetStatus;
use Livewire\Component;

class ActivityShow extends Component
{
    public Activity $activity;

    /** @var array<int, array<string,mixed>> */
    public array $reviews = [];

    /** @var array<int, mixed> */
    public array $availability = [];

    public int $reviewPage = 1;

    public bool $hasMoreReviews = false;

    public ?int $selectedSessionId = null;

    public int $participants = 1;

    private const REVIEWS_PER_PAGE = 5;

    public function mount($activity): void
    {
        $this->activity = Activity::published()
            ->with([
                'images',
                'amenities',
                'category',
                'destination',
                'options',
                'locations',
                'itinerary',
                'requirements',
                'languages',
                'mediaPosts' => fn ($query) => $query->publiclyPublished()
                    ->latest('published_at')
                    ->limit(6)
                    ->with(['assets' => fn ($assets) => $assets->where('status', MediaAssetStatus::Ready->value)]),
            ])
            ->where('slug', $activity->slug)
            ->firstOrFail();

        $this->loadInitialReviews();
        $this->loadAvailability();

    }

    /**
     * Load the first batch of reviews.
     */
    private function loadInitialReviews(): void
    {
        $allReviews = $this->activity->reviews()->where('status', 'approved')->latest();
        $this->hasMoreReviews = $allReviews->count() > self::REVIEWS_PER_PAGE;

        $this->reviews = $allReviews
            ->take(self::REVIEWS_PER_PAGE)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'user_name' => $r->guest?->user?->name ?? 'Guest',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Lazy-load more reviews on demand.
     */
    public function loadMoreReviews(): void
    {
        $this->reviewPage++;

        $moreReviews = $this->activity->reviews()
            ->where('status', 'approved')
            ->latest()
            ->skip(($this->reviewPage - 1) * self::REVIEWS_PER_PAGE)
            ->take(self::REVIEWS_PER_PAGE)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'user_name' => $r->guest?->user?->name ?? 'Guest',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at->diffForHumans(),
            ])
            ->toArray();

        $this->reviews = array_merge($this->reviews, $moreReviews);

        // Check if more reviews are available
        $totalReviews = $this->activity->reviews()->where('status', 'approved')->count();
        $this->hasMoreReviews = count($this->reviews) < $totalReviews;
    }

    /**
     * Load availability data for display (e.g., next month).
     */
    private function loadAvailability(): void
    {
        $this->availability = $this->activity->sessions()
            ->bookable()
            ->where('starts_at', '>=', now()->addMinutes($this->activity->minimum_notice_minutes))
            ->orderBy('starts_at')
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'starts_at' => $a->starts_at->toIso8601String(),
                'ends_at' => $a->ends_at->toIso8601String(),
                'timezone' => $a->timezone,
                'slots' => $a->availableCapacity(),
                'option_id' => $a->activity_option_id,
            ])->toArray();
    }

    /**
     * Trigger booking modal.
     */
    public function bookNow()
    {
        if (! auth()->check()) {
            return redirect()->guest(route('login'));
        }

        $this->validate([
            'selectedSessionId' => ['required', 'integer'],
            'participants' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $schedule = ActivitySession::query()
            ->where('activity_id', $this->activity->id)
            ->findOrFail($this->selectedSessionId);

        $booking = app(ActivitySessionBookingService::class)->book(
            $this->activity,
            $schedule,
            auth()->user(),
            $this->participants
        );

        return redirect()->route('booking-confirmation', $booking);
    }

    public function render()
    {
        return view('livewire.activity.activity-show');
    }
}
