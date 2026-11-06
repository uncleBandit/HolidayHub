<?php

namespace App\Modules\Agents\Presentation\Livewire\Agent;

use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Booking\Domain\Models\Booking;
use App\Modules\Packages\Domain\Models\Package;
use App\Support\Currencies;
use App\Support\Media;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The agent studio: how an agent's journeys are doing.
 *
 * Every figure comes from package bookings (bookings whose bookable is one of
 * the agent's packages); commission is the agent_commission recorded on each.
 * Packages are listed with their moderation status, and drafts resume where
 * the agent left off. Signed-in users who aren't agents yet are sent to apply.
 */
#[Layout('layouts.discovery')]
class AgentDashboard extends Component
{
    private const EARNING = ['confirmed', 'checked_in', 'checked_out', 'completed'];

    private const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=900&q=80';

    public function mount()
    {
        if (! Auth::user()->hasRole('agent') || ! Auth::user()->agent) {
            return $this->redirectRoute('agent.apply', navigate: false);
        }
    }

    public function render()
    {
        $agent = $this->agent();
        $packages = Package::query()
            ->where('agent_id', $agent->id)
            ->with('destination')
            ->withCount([
                'components',
                'bookings as bookings_count' => fn ($q) => $q->whereIn('status', self::EARNING),
            ])
            ->latest('updated_at')
            ->get();

        $bookings = Booking::query()
            ->where('bookable_type', 'package')
            ->whereIn('bookable_id', $packages->pluck('id'))
            ->with(['bookable', 'guest.user'])
            ->withCount('componentBookings')
            ->latest()
            ->get();

        $earning = $bookings->whereIn('status', self::EARNING);
        $currency = $this->mainCurrency($earning, $packages);
        $upcoming = $earning->filter(fn (Booking $b) => $b->check_in_date?->gte(today()))->sortBy('check_in_date')->values();

        return view('livewire.agent.agent-dashboard', [
            'agent' => $agent,
            'greeting' => match (true) {
                now()->hour < 12 => 'Good morning',
                now()->hour < 18 => 'Good afternoon',
                default => 'Good evening',
            },
            'kpis' => [
                'live' => $packages->where('status', 'published')->where('active', true)->count(),
                'packages' => $packages->count(),
                'in_review' => $packages->where('status', 'pending_review')->count(),
                'bookings' => $earning->count(),
                'bookings_30' => $earning->filter(fn ($b) => $b->created_at?->gte(now()->subDays(30)))->count(),
                'commission' => (float) $earning->where('currency', $currency)->sum('agent_commission'),
                'commission_other' => $earning->where('currency', '!=', $currency)->groupBy('currency')->map(fn ($g) => (float) $g->sum('agent_commission'))->all(),
                'currency' => $currency,
                'upcoming' => $upcoming->count(),
                'travellers' => (int) $upcoming->sum(fn ($b) => (int) $b->guests_adults + (int) $b->guests_children),
            ],
            'months' => $this->months($earning->where('currency', $currency)),
            'packages' => $packages->map(fn (Package $package) => $this->card($package))->all(),
            'departures' => $upcoming->take(6)->map(fn ($b) => $this->row($b))->all(),
            'recent' => $bookings->take(8)->map(fn ($b) => $this->row($b))->all(),
            'requirements' => $agent->isVerified() ? [] : ['agent_verified'],
        ])->title('Agent studio · HolidayHub');
    }

    private function agent(): Agent
    {
        return Auth::user()->agent;
    }

    private function card(Package $package): array
    {
        $status = $package->status ?: 'draft';

        return [
            'id' => $package->id,
            'name' => $package->name,
            'slug' => $package->slug,
            'image' => Media::url($package->cover_image) ?: self::FALLBACK_IMAGE,
            'film' => filled($package->cover_video),
            'place' => $package->destination?->name ?? 'No destination yet',
            'days' => (int) $package->duration_days,
            'parts' => (int) $package->components_count,
            'from' => (float) $package->base_price > 0 ? Currencies::format($package->base_price, $package->getCurrency()) : null,
            'bookings' => (int) $package->bookings_count,
            'status' => $status,
            'in_setup' => $package->setup_step !== null,
            'step' => $package->setup_step,
            'reason' => in_array($status, ['rejected', 'suspended'], true) ? $package->rejection_reason : null,
            'url' => $package->isPublished() ? route('packages-show', $package->slug) : null,
            'manage' => route('agent.packages.manage', $package->slug),
        ];
    }

    private function row(Booking $booking): array
    {
        $guest = $booking->guest?->user?->name ?? trim(($booking->guest?->first_name ?? '').' '.($booking->guest?->last_name ?? '')) ?: 'Guest';
        $in = $booking->check_in_date;
        $out = $booking->check_out_date;

        return [
            'id' => $booking->id,
            'guest' => $guest,
            'initials' => collect(preg_split('/\s+/', $guest))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
            'package' => $booking->bookable?->name ?? 'Package',
            'dates' => $in ? ($in->isSameMonth($out ?? $in) ? $in->format('j').'–'.($out ?? $in)->format('j M') : $in->format('j M').' – '.($out ?? $in)->format('j M')) : '—',
            'when' => $in ? ($in->isToday() ? 'Today' : ($in->isTomorrow() ? 'Tomorrow' : 'In '.(int) ceil(now()->diffInDays($in)).' days')) : '',
            'party' => (int) $booking->guests_adults + (int) $booking->guests_children,
            'parts' => (int) $booking->component_bookings_count,
            'total' => Currencies::format($booking->total_amount, $booking->currency),
            'commission' => Currencies::format($booking->agent_commission ?? 0, $booking->currency),
            'code' => $booking->confirmation_code,
            'booked' => $booking->created_at?->diffForHumans(),
            'status' => (string) $booking->status,
            'status_label' => Str::of((string) $booking->status)->replace('_', ' ')->title()->toString(),
        ];
    }

    /** Commission by departure month, the last four months and the next two. */
    private function months(Collection $bookings): array
    {
        return collect(range(-3, 2))->map(function (int $offset) use ($bookings) {
            $month = now()->startOfMonth()->addMonths($offset);
            $in = $bookings->filter(fn ($b) => $b->check_in_date && $b->check_in_date->isSameMonth($month));

            return [
                'label' => $month->format('M'),
                'long' => $month->format('F Y'),
                'total' => round((float) $in->sum('agent_commission'), 2),
                'count' => $in->count(),
                'current' => $offset === 0,
                'future' => $offset > 0,
            ];
        })->all();
    }

    /** The currency most of the agent's money is in (their packages' usual one). */
    private function mainCurrency(Collection $bookings, Collection $packages): string
    {
        return (string) ($bookings->countBy('currency')->sortDesc()->keys()->first()
            ?? $packages->countBy(fn ($p) => $p->getCurrency())->sortDesc()->keys()->first()
            ?? 'USD');
    }
}
