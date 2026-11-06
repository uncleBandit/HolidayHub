<?php

namespace App\Modules\Packages\Application\Services;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityOption;
use App\Modules\Packages\Domain\Models\Package;
use App\Modules\Packages\Domain\Models\PackageComponent;
use App\Support\Media;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Saves a package while its agent builds it and whenever they manage it.
 *
 * What guests read (the story, destinations, the day-by-day journey, photos
 * and film) sends a live package back to review when it changes. What only
 * decides the price and the party size (currency, margin, min and max
 * guests) is operational and saved quietly, as listing rates are. The
 * journey reel is saved by the SceneBuilder and never touches the package row.
 */
class PackageBuilder
{
    private const CORE = ['name', 'slug', 'short_description', 'full_description', 'destination_id', 'duration_days', 'inclusions', 'exclusions'];

    public function __construct(
        private readonly PackagePricing $pricing,
        private readonly PackagePublicationService $publication,
    ) {}

    /**
     * Create or update the package row (a draft until submitted).
     *
     * @param  array{name?: string, slug?: string, short_description?: string, full_description?: string, destination_id?: ?int, destinations?: array<int>, duration_days?: int, inclusions?: array, exclusions?: array, setup_step?: int}  $data
     */
    public function saveDraft(array $data, ?Package $package = null): Package
    {
        $agent = Auth::user()?->agent;
        if (! $agent || ! Auth::user()->hasRole('agent')) {
            throw new AuthorizationException('An agent account is required to build packages.');
        }

        return DB::transaction(function () use ($data, $package, $agent): Package {
            $extra = array_key_exists('destinations', $data) ? array_values(array_unique(array_map('intval', (array) $data['destinations']))) : null;
            $setupStep = $data['setup_step'] ?? null;
            $data = array_intersect_key($data, array_flip(self::CORE));

            $data['name'] = trim((string) ($data['name'] ?? '')) ?: 'Untitled journey';
            $data['slug'] = $this->uniqueSlug(filled($data['slug'] ?? null) ? $data['slug'] : $data['name'], $package);
            if (array_key_exists('duration_days', $data)) {
                $data['duration_days'] = max(1, min(60, (int) $data['duration_days']));
                $data['duration_nights'] = max(0, $data['duration_days'] - 1);
            }

            if ($package) {
                Gate::authorize('update', $package);

                $changed = false;
                foreach ($data as $key => $value) {
                    if (! $this->same($package->getAttribute($key), $value)) {
                        $package->setAttribute($key, $value);
                        $changed = true;
                    }
                }
                if ($extra !== null) {
                    $meta = (array) ($package->meta_data ?? []);
                    if (($meta['destinations'] ?? []) !== $extra) {
                        $meta['destinations'] = $extra;
                        $package->meta_data = $meta;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $package->save();
                    $this->publication->sendBackToReview($package, Auth::user());
                }

                return $package->refresh();
            }

            return Package::create($data + [
                'agent_id' => $agent->id,
                'status' => 'draft',
                'active' => false,
                'base_price' => 0,
                'currency' => 'USD',
                'markup_type' => 'percent',
                'markup_value' => 15,
                'min_guests' => 1,
                'max_guests' => 6,
                'setup_step' => $setupStep ?? 1,
                'meta_data' => ['destinations' => $extra ?? []],
            ]);
        });
    }

    /**
     * Replace the day-by-day journey. Each row: day, kind, and for listings
     * type (room_type|villa|bed_and_breakfast|activity) + listing_id, with
     * nights for stays and an optional option_id for experiences; extras
     * carry title, description, price and quantity.
     *
     * Only listings that are live on HolidayHub can be added.
     */
    public function saveComponents(Package $package, array $rows): Package
    {
        Gate::authorize('update', $package);

        $clean = [];
        foreach (array_values($rows) as $i => $row) {
            $clean[] = $this->component($row, $i, (int) $package->duration_days);
        }

        return DB::transaction(function () use ($package, $clean): Package {
            $before = $package->components()->get()->map(fn ($c) => $this->signature($c->toArray()))->values()->all();
            $after = array_map(fn ($row) => $this->signature($row), $clean);

            if ($before === $after) {
                return $package;
            }

            $package->components()->delete();
            foreach ($clean as $row) {
                $package->components()->create($row);
            }
            $package->touch();
            $this->publication->sendBackToReview($package, Auth::user());

            return $package->refresh();
        });
    }

    /**
     * Cover photo, cover film and gallery. Paths already stored (including
     * media borrowed from the listings in the journey) are kept as they are.
     */
    public function saveMedia(Package $package, array $media): Package
    {
        Gate::authorize('update', $package);
        $stored = [];

        $store = function (UploadedFile $file, string $folder) use (&$stored): string {
            $folder .= Media::isVideo($file->getClientOriginalName()) ? '/videos' : '';
            $path = $file->store($folder, 'public');
            if ($path === false) {
                throw new \RuntimeException('Unable to store an uploaded file.');
            }

            return $stored[] = $path;
        };

        try {
            return DB::transaction(function () use ($package, $media, $store): Package {
                $cover = ($media['cover'] ?? null) instanceof UploadedFile ? $store($media['cover'], 'packages/covers') : ($media['cover_path'] ?? null);
                $video = ($media['cover_video'] ?? null) instanceof UploadedFile ? $store($media['cover_video'], 'packages/covers') : ($media['cover_video_path'] ?? null);
                $gallery = array_values(array_unique(array_merge(
                    (array) ($media['gallery_paths'] ?? []),
                    array_map(fn (UploadedFile $file) => $store($file, 'packages/gallery'), array_filter((array) ($media['new_gallery'] ?? []), fn ($f) => $f instanceof UploadedFile))
                )));

                $package->cover_image = $cover;
                $package->cover_video = $video;
                $package->gallery = $gallery;

                if ($package->isDirty()) {
                    $package->save();
                    $this->publication->sendBackToReview($package, Auth::user());
                }

                return $package->refresh();
            });
        } catch (\Throwable $e) {
            if ($stored !== []) {
                Storage::disk('public')->delete($stored);
            }
            Log::error('Package media save failed.', ['package_id' => $package->id, 'error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Currency, margin and party size: saved quietly (a live package stays
     * live), then the cached "from" price is refreshed.
     *
     * @param  array{currency?: string, markup_type?: string, markup_value?: mixed, min_guests?: mixed, max_guests?: mixed}  $pricing
     */
    public function savePricing(Package $package, array $pricing): Package
    {
        Gate::authorize('update', $package);

        $changes = [];
        if (filled($pricing['currency'] ?? null)) {
            $changes['currency'] = Str::upper($pricing['currency']);
        }
        if (in_array($pricing['markup_type'] ?? null, ['percent', 'fixed'], true)) {
            $changes['markup_type'] = $pricing['markup_type'];
        }
        if (array_key_exists('markup_value', $pricing) && is_numeric($pricing['markup_value'])) {
            $changes['markup_value'] = max(0, round((float) $pricing['markup_value'], 2));
        }
        if (is_numeric($pricing['min_guests'] ?? null)) {
            $changes['min_guests'] = max(1, min(50, (int) $pricing['min_guests']));
        }
        if (is_numeric($pricing['max_guests'] ?? null)) {
            $changes['max_guests'] = max($changes['min_guests'] ?? (int) $package->min_guests, min(50, (int) $pricing['max_guests']));
        }

        $changes = array_filter($changes, fn ($value, $key) => ! $this->same($package->getAttribute($key), $value), ARRAY_FILTER_USE_BOTH);
        if ($changes !== []) {
            $package->forceFill($changes)->saveQuietly();
        }

        return $this->refreshFromPrice($package);
    }

    /**
     * Cache the "from" price guests see on cards: the per-person price of the
     * first bookable departure, for the smallest sensible party.
     */
    public function refreshFromPrice(Package $package): Package
    {
        try {
            $guests = max(1, min((int) $package->max_guests ?: 2, max(2, (int) $package->min_guests)));
            $quote = $this->pricing->sampleQuote($package->fresh(), $guests);
            $from = $quote['total'] > 0 ? $quote['per_person'] : 0;

            if ((float) $package->base_price !== (float) $from) {
                $package->forceFill(['base_price' => $from])->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::warning('Package from-price not refreshed', ['package_id' => $package->id, 'error' => $e->getMessage()]);
        }

        return $package->refresh();
    }

    /**
     * Photos and films borrowed from the listings in the journey, for the
     * "use media from the listings" shortcut: [cover, film, gallery paths].
     *
     * @return array{cover: ?string, film: ?string, gallery: array<int, string>}
     */
    public function listingMedia(Package $package): array
    {
        $this->pricing->ensureLoaded($package);
        $photos = collect();
        $films = collect();

        foreach ($package->components->filter->isListing() as $component) {
            $listing = $component->componentable;
            $owner = $listing instanceof RoomType ? $listing->hotel : $listing;
            if (! $owner) {
                continue;
            }

            $candidates = array_merge(
                [$owner->cover_video ?? null, $owner->cover_image ?? null, $owner->thumbnail ?? null],
                $listing instanceof RoomType ? [$listing->hero_image_url] : [],
                (array) ($owner->gallery ?? [])
            );

            foreach (array_filter($candidates, fn ($p) => is_string($p) && filled($p) && ! Str::contains($p, ['placeholder', 'placehold', 'picsum', 'default-hero', 'source.unsplash'])) as $path) {
                Media::isVideo($path) ? $films->push($path) : $photos->push($path);
            }
        }

        $photos = $photos->unique()->values();
        $films = $films->unique()->values();

        return [
            'cover' => $photos->first(),
            'film' => $films->first(),
            'gallery' => $films->skip(1)->take(3)->concat($photos->skip(1)->take(9))->values()->all(),
        ];
    }

    // ── Internals ────────────────────────────────────────────────────────

    private function component(array $row, int $index, int $days): array
    {
        $kind = $row['kind'] ?? null;
        if (! array_key_exists((string) $kind, PackageComponent::KINDS)) {
            throw ValidationException::withMessages(["components.$index" => 'Choose what this part of the day is.']);
        }

        $base = [
            'day' => max(1, min(max(1, $days), (int) ($row['day'] ?? 1))),
            'sequence' => $index,
            'kind' => $kind,
            'componentable_type' => null,
            'componentable_id' => null,
            'activity_option_id' => null,
            'nights' => null,
            'quantity' => max(1, min(50, (int) ($row['quantity'] ?? 1))),
            'title' => Str::limit(trim((string) ($row['title'] ?? '')), 160, '') ?: null,
            'description' => trim((string) ($row['description'] ?? '')) ?: null,
            'price' => null,
            'currency' => null,
        ];

        if ($kind === 'stay') {
            $listing = match ($row['type'] ?? null) {
                'room_type' => RoomType::with('hotel.accommodation')->find($row['listing_id'] ?? 0),
                'villa' => Villa::with('accommodation')->find($row['listing_id'] ?? 0),
                'bed_and_breakfast' => BedAndBreakfast::with('accommodation')->find($row['listing_id'] ?? 0),
                default => null,
            };
            $live = $listing instanceof RoomType ? $listing->hotel?->accommodation?->isPublished() : $listing?->accommodation?->isPublished();
            if (! $listing || ! $live) {
                throw ValidationException::withMessages(["components.$index" => 'Choose a stay that is live on HolidayHub.']);
            }

            return [
                'componentable_type' => $listing->getMorphClass(),
                'componentable_id' => $listing->getKey(),
                'nights' => max(1, min(30, (int) ($row['nights'] ?? 1))),
                'quantity' => 1,
                'title' => $base['title'] ?? ($listing instanceof RoomType ? $listing->hotel?->name.' · '.$listing->name : $listing->name),
            ] + $base;
        }

        if ($kind === 'experience') {
            $activity = Activity::find($row['listing_id'] ?? 0);
            if (! $activity || ! $activity->isPublished()) {
                throw ValidationException::withMessages(["components.$index" => 'Choose an experience that is live on HolidayHub.']);
            }
            $option = filled($row['option_id'] ?? null)
                ? ActivityOption::where('activity_id', $activity->id)->where('is_active', true)->find($row['option_id'])
                : null;

            return [
                'componentable_type' => $activity->getMorphClass(),
                'componentable_id' => $activity->id,
                'activity_option_id' => $option?->id,
                'quantity' => 1,
                'title' => $base['title'] ?? ($option ? $activity->name.' · '.$option->name : $activity->name),
            ] + $base;
        }

        // Extras: the agent's own price (none for a note).
        if ($kind !== 'note' && filled($row['price'] ?? null)) {
            $base['price'] = max(0, round((float) $row['price'], 2));
            $base['currency'] = Str::upper((string) ($row['currency'] ?? '')) ?: null;
        }

        return $base;
    }

    private function signature(array $row): string
    {
        return json_encode(array_map(fn ($v) => is_numeric($v) ? (float) $v : $v, array_intersect_key($row, array_flip([
            'day', 'sequence', 'kind', 'componentable_type', 'componentable_id', 'activity_option_id', 'nights', 'quantity', 'title', 'description', 'price', 'currency',
        ]))));
    }

    private function same(mixed $stored, mixed $wanted): bool
    {
        $empty = fn ($value) => $value === null || $value === '' || $value === [];

        if ($empty($stored) || $empty($wanted)) {
            return $empty($stored) && $empty($wanted);
        }
        if (is_array($stored) || is_array($wanted)) {
            return (array) $stored === (array) $wanted;
        }
        if (is_numeric($stored) && is_numeric($wanted)) {
            return (float) $stored === (float) $wanted;
        }

        return (string) $stored === (string) $wanted;
    }

    private function uniqueSlug(string $wanted, ?Package $package = null): string
    {
        $base = Str::slug($wanted) ?: 'journey';
        $slug = $base;
        $n = 1;

        while (Package::withTrashed()->where('slug', $slug)->when($package, fn ($q) => $q->whereKeyNot($package->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
