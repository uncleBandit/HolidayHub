<?php

namespace App\Modules\Packages\Application\Services;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Audit\Application\Services\AuditRecorder;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Domain\Models\PackageComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Throwable;

/**
 * A package's moderation lifecycle, like the other listings:
 *
 *   draft ──submit──▶ pending_review ──approve──▶ published ──suspend──▶ suspended
 *     ▲                     │                        │                     │
 *     └──── (agent edits) ◀─┴──reject──▶ rejected ◀──┘           reinstate─┘
 *
 * The agent submits from the builder; admins approve, reject or suspend from
 * the admin panel. Submitting needs a verified agent and a complete package
 * (see requirements()); approving re-checks the same list, so nothing goes
 * live half-built or with a listing that has since gone offline.
 *
 * Every decision is appended to meta_data['moderation'] (who, what, when,
 * why) and written to the platform audit log.
 */
class PackagePublicationService
{
    public function __construct(private readonly PackagePricing $pricing) {}

    /**
     * The checklist a package must pass to be submitted or approved.
     *
     * @return array<string, array{label: string, met: bool, hint: string}>
     */
    public function requirements(Package $package): array
    {
        $package->load('agent');
        $this->pricing->ensureLoaded($package);

        $components = $package->components;
        $listings = $components->filter(fn (PackageComponent $c) => $c->isListing());
        $days = max(1, (int) $package->duration_days);
        $offline = $listings->reject(fn (PackageComponent $c) => $this->listingIsLive($c));
        $currency = $package->getCurrency();
        $unconvertible = $listings
            ->map(fn (PackageComponent $c) => $c->componentable && method_exists($c->componentable, 'getCurrency') ? Str::upper($c->componentable->getCurrency()) : null)
            ->filter()
            ->unique()
            ->reject(fn ($code) => $this->pricing->canConvert($code, $currency));
        $emptyDays = collect(range(1, $days))->diff($components->pluck('day')->map(fn ($d) => (int) $d)->unique())->values();

        return [
            'agent_verified' => [
                'label' => 'Verified agent',
                'met' => (bool) $package->agent?->isVerified(),
                'hint' => 'Our team verifies your agency before your packages can go live. You can keep building meanwhile.',
            ],
            'story' => [
                'label' => 'Name, hook and story',
                'met' => filled($package->name) && mb_strlen((string) $package->short_description) >= 20 && mb_strlen((string) $package->full_description) >= 40,
                'hint' => 'A name, a one-line hook (20+ characters) and the story (40+ characters).',
            ],
            'destination' => [
                'label' => 'Destination',
                'met' => $package->destination_id !== null,
                'hint' => 'Choose where the journey goes.',
            ],
            'listings' => [
                'label' => 'At least one stay or experience',
                'met' => $listings->isNotEmpty(),
                'hint' => 'Build the journey from stays and experiences already on HolidayHub.',
            ],
            'days' => [
                'label' => 'Something on every day',
                'met' => $emptyDays->isEmpty() && (int) $components->max('day') <= $days,
                'hint' => $emptyDays->isEmpty()
                    ? 'Every day of the journey has a plan.'
                    : 'Add a stay, experience, transfer, free time or a note to day '.$emptyDays->implode(', ').'.',
            ],
            'live_listings' => [
                'label' => 'Every listing is live',
                'met' => $offline->isEmpty(),
                'hint' => $offline->isEmpty()
                    ? 'All the listings in this journey take bookings.'
                    : 'Replace '.$offline->map(fn ($c) => $c->title ?: 'a listing')->implode(', ').'; it no longer takes bookings.',
            ],
            'cover' => [
                'label' => 'A cover photo or film',
                'met' => filled($package->cover_image) || filled($package->cover_video),
                'hint' => 'Guests see it first, on cards and on the package page.',
            ],
            'pricing' => [
                'label' => 'Currency, margin and group size',
                'met' => $this->pricing->rate($currency) !== null
                    && in_array($package->markup_type, ['percent', 'fixed'], true)
                    && (float) $package->markup_value >= 0
                    && (int) $package->min_guests >= 1
                    && (int) $package->max_guests >= (int) $package->min_guests,
                'hint' => 'A package currency we can convert into, your margin, and the smallest and largest group.',
            ],
            'convertible' => [
                'label' => 'Prices convert into the package currency',
                'met' => $unconvertible->isEmpty(),
                'hint' => $unconvertible->isEmpty()
                    ? 'Every listing price can be shown in '.$currency.'.'
                    : 'We have no rate for '.$unconvertible->implode(', ').'. Choose another package currency.',
            ],
        ];
    }

    /** The requirements not yet met, as labels. */
    public function missing(Package $package): array
    {
        return collect($this->requirements($package))->reject(fn ($r) => $r['met'])->map(fn ($r) => $r['label'])->values()->all();
    }

    public function canSubmit(Package $package): bool
    {
        return in_array($package->status, ['draft', 'rejected'], true) && $this->missing($package) === [];
    }

    /** The agent sends a draft (or a rejected package) to the review queue. */
    public function submit(Package $package, ?User $actor = null): Package
    {
        if (! in_array($package->status, ['draft', 'rejected'], true)) {
            throw new LogicException('Only draft or rejected packages can be submitted for review.');
        }

        $this->ensureComplete($package);

        return $this->transition($package, 'pending_review', 'submitted', $actor, null, [
            'submitted_at' => now(),
            'rejection_reason' => null,
            'setup_step' => null,
            'active' => false,
        ]);
    }

    /** An admin puts a submitted package live. */
    public function approve(Package $package, ?User $reviewer = null): Package
    {
        if ($package->status !== 'pending_review') {
            throw new LogicException('Only packages in review can be approved.');
        }

        $this->ensureComplete($package);

        return $this->transition($package, 'published', 'approved', $reviewer, null, [
            'published_at' => now(),
            'rejection_reason' => null,
            'setup_step' => null,
            'active' => true,
        ]);
    }

    /** An admin sends a package back to its agent with a reason. */
    public function reject(Package $package, string $reason, ?User $reviewer = null): Package
    {
        if (! in_array($package->status, ['pending_review', 'published'], true)) {
            throw new LogicException('Only packages in review or live can be rejected.');
        }
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Say what the agent needs to change.']);
        }

        return $this->transition($package, 'rejected', 'rejected', $reviewer, $reason, [
            'rejection_reason' => trim($reason),
            'published_at' => null,
            'active' => false,
        ]);
    }

    /** An admin takes a live package down (it keeps its bookings). */
    public function suspend(Package $package, string $reason = '', ?User $reviewer = null): Package
    {
        if ($package->status !== 'published') {
            throw new LogicException('Only live packages can be suspended.');
        }

        return $this->transition($package, 'suspended', 'suspended', $reviewer, $reason ?: null, [
            'rejection_reason' => filled($reason) ? trim($reason) : null,
            'active' => false,
        ]);
    }

    /** An admin puts a suspended package back live, after re-checking it. */
    public function reinstate(Package $package, ?User $reviewer = null): Package
    {
        if ($package->status !== 'suspended') {
            throw new LogicException('Only suspended packages can be reinstated.');
        }

        $this->ensureComplete($package);

        return $this->transition($package, 'published', 'reinstated', $reviewer, null, [
            'rejection_reason' => null,
            'published_at' => now(),
            'active' => true,
        ]);
    }

    /**
     * A live package whose guest-facing content changed goes back to review
     * (the agent's edit is kept; guests can't book it until it's approved).
     */
    public function sendBackToReview(Package $package, ?User $actor = null): Package
    {
        if ($package->status !== 'published') {
            return $package;
        }

        return $this->transition($package, 'pending_review', 'resubmitted', $actor, 'Changed while live.', [
            'submitted_at' => now(),
            'published_at' => null,
            'active' => false,
        ]);
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function ensureComplete(Package $package): void
    {
        $requirements = $this->requirements($package);

        if (! $requirements['agent_verified']['met']) {
            throw ValidationException::withMessages([
                'package' => 'Your agent profile is still being verified. You can keep building; you’ll be able to submit once our team has verified your agency.',
            ]);
        }

        $missing = collect($requirements)->reject(fn ($r) => $r['met']);
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'package' => 'Complete these before submitting: '.$missing->map(fn ($r) => Str::lower($r['label']))->implode(', ').'.',
            ]);
        }
    }

    private function transition(Package $package, string $status, string $action, ?User $actor, ?string $reason, array $changes): Package
    {
        $before = $package->status;

        return DB::transaction(function () use ($package, $status, $action, $actor, $reason, $changes, $before): Package {
            $meta = (array) ($package->meta_data ?? []);
            $meta['moderation'] = array_values(array_slice(array_merge((array) ($meta['moderation'] ?? []), [[
                'action' => $action,
                'from' => $before,
                'to' => $status,
                'by' => $actor?->id,
                'at' => now()->toIso8601String(),
                'reason' => $reason,
            ]]), -30));

            $package->forceFill(['status' => $status, 'meta_data' => $meta] + $changes)->save();

            try {
                // In its own savepoint, so a failed audit write can't poison the transaction.
                DB::transaction(fn () => app(AuditRecorder::class)->record('package.'.$action, $package, $actor, before: ['status' => $before], after: ['status' => $status], reason: $reason));
            } catch (Throwable $e) {
                // The audit log is best effort; the decision itself stands.
                report($e);
            }

            return $package->refresh();
        });
    }

    private function listingIsLive(PackageComponent $component): bool
    {
        $listing = $component->componentable;

        return match (true) {
            $listing instanceof RoomType => (bool) $listing->hotel?->accommodation?->isPublished(),
            $listing instanceof Villa, $listing instanceof BedAndBreakfast => (bool) $listing->accommodation?->isPublished(),
            $listing instanceof Activity => $listing->isPublished(),
            default => false,
        };
    }
}
