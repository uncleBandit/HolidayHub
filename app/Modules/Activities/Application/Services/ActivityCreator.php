<?php

namespace App\Modules\Activities\Application\Services;

use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivitySchedule;
use App\Shared\Support\Media;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Saves an experience while its host sets it up and whenever they manage it.
 *
 * What guests read (the story, photos, plan, meeting point, who it's for)
 * goes back to review when it changes on a live experience: the Activity
 * model's updating hook does that for its own listing columns, and this
 * service does the same for the cover film, the cancellation policy and the
 * related rows (itinerary, requirements, languages, meeting point), which the
 * hook can't see.
 *
 * What only decides when and for how much (price, currency, timezone, booking
 * cutoff, notice, the weekly schedule and its sessions) is operational: it is
 * saved quietly and goes live straight away, as hotel and villa rates do.
 * Scenes (the reel walk) are saved by the SceneBuilder and never touch the
 * activity row.
 */
class ActivityCreator
{
    /** Activity columns this service writes with a normal (reviewed) save. */
    private const CORE = [
        'name', 'slug', 'category_id', 'type', 'destination_id', 'short_description', 'description', 'highlights',
        'inclusions', 'exclusions', 'duration_minutes', 'capacity', 'min_age', 'max_age', 'accessibility',
        'weather_dependent', 'weather_cancellation_policy', 'safety_instructions',
    ];

    /**
     * Create or update the experience row itself (a draft until submitted).
     *
     * `attributes` is merged into what is stored, so keys this form doesn't
     * know about are kept.
     */
    public function saveDraft(array $data, ?Activity $activity = null): Activity
    {
        $provider = Auth::user()?->provider;
        if (! $provider || ! Auth::user()->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        return DB::transaction(function () use ($data, $activity, $provider): Activity {
            $attributes = array_key_exists('attributes', $data) ? (array) $data['attributes'] : null;
            $setupStep = $data['setup_step'] ?? null;
            $data = array_intersect_key($data, array_flip(self::CORE));

            $data['name'] = trim((string) ($data['name'] ?? '')) ?: 'Untitled experience';
            $data['slug'] = $this->uniqueSlug(filled($data['slug'] ?? null) ? $data['slug'] : $data['name'], $activity);

            if ($activity) {
                Gate::authorize('update', $activity);

                // Only assign what really changed, so an untouched experience
                // compares clean and a live one is never sent back to review.
                foreach ($data as $key => $value) {
                    if (! $this->same($activity->getAttribute($key), $value)) {
                        $activity->setAttribute($key, $value);
                    }
                }

                $reviewExtras = false;
                if ($attributes !== null) {
                    $merged = array_replace((array) ($activity->getAttribute('attributes') ?? []), $attributes);
                    if (! $this->same($activity->getAttribute('attributes'), $merged)) {
                        // The cancellation policy is guest-facing; booking settings are not.
                        $reviewExtras = ($activity->getAttribute('attributes')['cancellation_policy'] ?? null) !== ($merged['cancellation_policy'] ?? null);
                        $activity->setAttribute('attributes', $merged);
                    }
                }

                if ($activity->isDirty()) {
                    $activity->save();
                }
                if ($reviewExtras) {
                    $this->sendBackToReview($activity);
                }
            } else {
                Gate::authorize('create', Activity::class);
                $activity = new Activity($data);
                $activity->setAttribute('attributes', $attributes ?? []);
                $activity->provider_id = $provider->id;
                $activity->booking_mode = 'shared';
                $activity->included_participants = 1;
                $activity->setup_step = $setupStep ?? 1;
                $activity->save();
            }

            Log::info('Experience draft saved.', ['activity_id' => $activity->id, 'provider_id' => $provider->id]);

            return $activity->fresh(['category', 'destination']);
        });
    }

    /**
     * The meeting point, languages, itinerary and requirements: each set is
     * rewritten only when it changed.
     *
     * @param  array{meeting_point?: ?array, languages?: array<int, string>, itinerary?: array<int, array>, requirements?: array<int, array>}  $details
     */
    public function saveDetails(Activity $activity, array $details): Activity
    {
        Gate::authorize('update', $activity);

        return DB::transaction(function () use ($activity, $details): Activity {
            $changed = false;

            if (array_key_exists('meeting_point', $details) && is_array($point = $details['meeting_point'])) {
                $stored = $activity->locations()->where('type', 'meeting_point')->orderBy('sequence')->first();
                $wanted = [
                    'name' => trim((string) ($point['name'] ?? '')) ?: 'Meeting point',
                    'address' => trim((string) ($point['address'] ?? '')) ?: null,
                    'latitude' => filled($point['latitude'] ?? null) ? round((float) $point['latitude'], 7) : null,
                    'longitude' => filled($point['longitude'] ?? null) ? round((float) $point['longitude'], 7) : null,
                    'instructions' => trim((string) ($point['instructions'] ?? '')) ?: null,
                ];
                $current = $stored ? [
                    'name' => (string) $stored->name,
                    'address' => $stored->address,
                    'latitude' => $stored->latitude !== null ? round((float) $stored->latitude, 7) : null,
                    'longitude' => $stored->longitude !== null ? round((float) $stored->longitude, 7) : null,
                    'instructions' => $stored->instructions,
                ] : null;

                if ($current !== $wanted) {
                    $stored ? $stored->update($wanted) : $activity->locations()->create($wanted + ['type' => 'meeting_point', 'sequence' => 1]);
                    $changed = true;
                }
            }

            if (array_key_exists('languages', $details)) {
                $wanted = collect($details['languages'])->map(fn ($code) => Str::lower(trim((string) $code)))->filter()->unique()->sort()->values()->all();
                $current = $activity->languages()->pluck('language_code')->map(fn ($code) => Str::lower($code))->sort()->values()->all();
                if ($wanted !== $current) {
                    $activity->languages()->delete();
                    foreach ($wanted as $code) {
                        $activity->languages()->create(['language_code' => $code]);
                    }
                    $changed = true;
                }
            }

            if (array_key_exists('itinerary', $details)) {
                $wanted = collect($details['itinerary'])
                    ->filter(fn ($item) => filled($item['title'] ?? null))
                    ->values()
                    ->map(fn ($item, $i) => [
                        'sequence' => $i + 1,
                        'title' => trim((string) $item['title']),
                        'description' => trim((string) ($item['description'] ?? '')) ?: null,
                        'duration_minutes' => filled($item['minutes'] ?? null) && (int) $item['minutes'] > 0 ? (int) $item['minutes'] : null,
                    ])->all();
                $current = $activity->itinerary()->get()->map(fn ($item) => [
                    'sequence' => (int) $item->sequence,
                    'title' => (string) $item->title,
                    'description' => $item->description,
                    'duration_minutes' => $item->duration_minutes !== null ? (int) $item->duration_minutes : null,
                ])->values()->all();

                if ($wanted !== $current) {
                    $activity->itinerary()->delete();
                    foreach ($wanted as $item) {
                        $activity->itinerary()->create($item);
                    }
                    $changed = true;
                }
            }

            if (array_key_exists('requirements', $details)) {
                $normalise = fn ($rows) => collect($rows)
                    ->filter(fn ($row) => filled($row['title'] ?? null))
                    ->map(fn ($row) => [
                        'type' => (string) ($row['type'] ?? 'general'),
                        'title' => trim((string) $row['title']),
                        'description' => trim((string) ($row['description'] ?? '')) ?: null,
                        'required' => (bool) ($row['required'] ?? false),
                    ])->values()->all();
                $wanted = $normalise($details['requirements']);
                $current = $normalise($activity->requirements()->orderBy('id')->get()->map->only(['type', 'title', 'description', 'required'])->all());

                if ($wanted !== $current) {
                    $activity->requirements()->delete();
                    foreach ($wanted as $row) {
                        $activity->requirements()->create($row);
                    }
                    $changed = true;
                }
            }

            if ($changed) {
                $this->sendBackToReview($activity);
                Log::info('Experience details saved.', ['activity_id' => $activity->id]);
            }

            return $activity->fresh(['category', 'destination']);
        });
    }

    /**
     * Save the cover photo, cover film and gallery (photos and films).
     *
     * @param  array{cover?: ?UploadedFile, cover_path?: ?string, cover_video?: ?UploadedFile, cover_video_path?: ?string, gallery_paths?: array<int, string>, new_gallery?: array<int, UploadedFile>}  $media
     */
    public function saveMedia(Activity $activity, array $media): Activity
    {
        return $this->withStorage($activity, 'media', function (callable $store) use ($activity, $media): Activity {
            // Only touch the columns when they actually change, so an unchanged
            // gallery never sends a live experience back to review.
            $cover = ($media['cover'] ?? null) instanceof UploadedFile ? $store($media['cover'], 'activities/covers') : ($media['cover_path'] ?? null);
            $video = ($media['cover_video'] ?? null) instanceof UploadedFile ? $store($media['cover_video'], 'activities/covers') : ($media['cover_video_path'] ?? null);
            $gallery = array_values(array_merge(
                (array) ($media['gallery_paths'] ?? []),
                array_map(fn (UploadedFile $file) => $store($file, 'activities/gallery'), array_filter((array) ($media['new_gallery'] ?? []), fn ($file) => $file instanceof UploadedFile))
            ));

            if ($cover !== $activity->thumbnail) {
                $activity->thumbnail = $cover;
            }
            if ($gallery !== array_values((array) ($activity->gallery ?? []))) {
                $activity->gallery = $gallery;
            }
            // cover_video isn't one of the model's listing fields, so a new
            // film on a live experience is sent for review here.
            $filmChanged = $video !== $activity->cover_video;
            if ($filmChanged) {
                $activity->cover_video = $video;
            }
            if ($activity->isDirty()) {
                $activity->save();
            }
            if ($filmChanged) {
                $this->sendBackToReview($activity);
            }

            return $activity->fresh(['category', 'destination']);
        });
    }

    /**
     * Price per person, currency, timezone, booking cutoff and minimum notice:
     * saved quietly, so a live experience stays live.
     *
     * @param  array{base_price?: mixed, currency?: string, timezone?: string, booking_cutoff_minutes?: ?int, minimum_notice_minutes?: int}  $pricing
     */
    public function savePricing(Activity $activity, array $pricing): Activity
    {
        Gate::authorize('update', $activity);

        return DB::transaction(function () use ($activity, $pricing): Activity {
            $changes = [];

            if (filled($pricing['base_price'] ?? null) && (float) $pricing['base_price'] > 0) {
                $price = round((float) $pricing['base_price'], 2);
                if ($activity->base_price === null || (float) $activity->base_price !== $price) {
                    $changes['base_price'] = $price;
                }
            }
            if (filled($pricing['currency'] ?? null) && $pricing['currency'] !== $activity->currency) {
                $changes['currency'] = Str::upper($pricing['currency']);
            }
            if (filled($pricing['timezone'] ?? null) && $pricing['timezone'] !== $activity->timezone) {
                $changes['timezone'] = $pricing['timezone'];
            }
            if (array_key_exists('booking_cutoff_minutes', $pricing)) {
                $cutoff = $pricing['booking_cutoff_minutes'] === null || $pricing['booking_cutoff_minutes'] === '' ? null : max(0, (int) $pricing['booking_cutoff_minutes']);
                if ($cutoff !== ($activity->booking_cutoff_minutes === null ? null : (int) $activity->booking_cutoff_minutes)) {
                    $changes['booking_cutoff_minutes'] = $cutoff;
                }
            }
            if (array_key_exists('minimum_notice_minutes', $pricing) && max(0, (int) $pricing['minimum_notice_minutes']) !== (int) $activity->minimum_notice_minutes) {
                $changes['minimum_notice_minutes'] = max(0, (int) $pricing['minimum_notice_minutes']);
            }

            if ($changes !== []) {
                $activity->updateQuietly($changes);
            }

            // A new cutoff applies to every upcoming session, including those
            // of schedules that carried their own.
            if (array_key_exists('booking_cutoff_minutes', $changes)) {
                $activity->schedules()->whereNotNull('booking_cutoff_minutes')->update(['booking_cutoff_minutes' => null]);
                $this->refreshCutoffs($activity);
            }

            Log::info('Experience pricing saved.', ['activity_id' => $activity->id, 'changed' => array_keys($changes)]);

            return $activity->fresh(['category', 'destination']);
        });
    }

    /**
     * Make the weekly schedule match the rows the host keeps, then open
     * sessions for the next `weeks` weeks.
     *
     * Sessions that already have bookings are never removed: a slot the host
     * drops is switched off, and its booked sessions stay on the calendar.
     *
     * @param  array<int, array{days?: array<int, int|string>, start?: string, end?: string, capacity?: mixed}>  $rows
     * @return array{schedules: int, sessions: int, kept: int}
     */
    public function saveSchedule(Activity $activity, array $rows, int $weeks, ?string $timezone = null): array
    {
        Gate::authorize('update', $activity);

        return DB::transaction(function () use ($activity, $rows, $weeks, $timezone): array {
            $timezone = $timezone ?: ($activity->timezone ?: 'UTC');
            $weeks = max(1, min(52, $weeks));

            // One schedule per weekday and start time.
            $wanted = collect($rows)
                ->filter(fn ($row) => preg_match('/^\d{2}:\d{2}$/', (string) ($row['start'] ?? '')) && preg_match('/^\d{2}:\d{2}$/', (string) ($row['end'] ?? '')) && $row['start'] !== $row['end'] && (int) ($row['capacity'] ?? 0) > 0)
                ->flatMap(fn ($row) => collect((array) ($row['days'] ?? []))
                    ->map(fn ($day) => (int) $day)
                    ->filter(fn ($day) => $day >= 0 && $day <= 6)
                    ->map(fn ($day) => ['day_of_week' => $day, 'start_time' => $row['start'], 'end_time' => $row['end'], 'capacity' => (int) $row['capacity']]))
                ->keyBy(fn ($slot) => $slot['day_of_week'].'@'.$slot['start_time']);

            $stored = $activity->schedules()->whereNull('activity_option_id')->get()
                ->keyBy(fn (ActivitySchedule $schedule) => $schedule->day_of_week.'@'.substr((string) $schedule->start_time, 0, 5));
            $kept = 0;

            // Slots the host no longer runs.
            foreach ($stored->diffKeys($wanted) as $schedule) {
                $this->clearOpenSessions($schedule, past: true);
                if ($schedule->sessions()->exists()) {
                    $kept += $schedule->sessions()->where('starts_at', '>=', now())->count();
                    if ($schedule->is_active) {
                        $schedule->update(['is_active' => false]);
                    }
                } else {
                    $schedule->delete();
                }
            }

            foreach ($wanted as $key => $slot) {
                $schedule = $stored->get($key);

                if (! $schedule) {
                    $schedule = $activity->schedules()->create($slot + [
                        'timezone' => $timezone,
                        'active_from' => now()->toDateString(),
                        'is_active' => true,
                    ]);

                    continue;
                }

                $changed = substr((string) $schedule->end_time, 0, 5) !== $slot['end_time']
                    || (int) $schedule->capacity !== $slot['capacity']
                    || $schedule->timezone !== $timezone;

                if ($changed) {
                    $this->clearOpenSessions($schedule);
                    $schedule->sessions()->where('starts_at', '>=', now())->get()
                        ->each(fn ($session) => $session->update(['capacity' => max($session->booked_capacity, $slot['capacity'])]));
                }

                $schedule->fill([
                    'end_time' => $slot['end_time'],
                    'capacity' => $slot['capacity'],
                    'timezone' => $timezone,
                    'is_active' => true,
                    'active_until' => null,
                ]);
                if ($schedule->isDirty()) {
                    $schedule->save();
                }
            }

            $generator = app(ActivityScheduleGenerator::class);
            $sessions = 0;
            foreach ($activity->schedules()->where('is_active', true)->whereNull('activity_option_id')->get() as $schedule) {
                $schedule->setRelation('activity', $activity);
                $sessions += $generator->generate($schedule, now(), now()->addWeeks($weeks))->count();
            }

            Log::info('Experience schedule saved.', ['activity_id' => $activity->id, 'slots' => $wanted->count(), 'sessions' => $sessions]);

            return ['schedules' => $wanted->count(), 'sessions' => $sessions, 'kept' => $kept];
        });
    }

    /**
     * Put a live experience back in front of the review team, as the model's
     * updating hook does for its own listing columns.
     */
    private function sendBackToReview(Activity $activity): void
    {
        if ($activity->status !== ActivityStatus::Published) {
            return;
        }

        $activity->forceFill([
            'status' => ActivityStatus::Draft,
            'verification_status' => ActivityVerificationStatus::Pending,
            'is_active' => false,
            'published_at' => null,
        ])->save();
    }

    /** Remove a schedule's sessions that nobody has booked (upcoming only unless `past`). */
    private function clearOpenSessions(ActivitySchedule $schedule, bool $past = false): void
    {
        $schedule->sessions()
            ->when(! $past, fn ($query) => $query->where('starts_at', '>=', now()))
            ->where('booked_capacity', 0)
            ->whereDoesntHave('bookings')
            ->delete();
    }

    private function refreshCutoffs(Activity $activity): void
    {
        $minutes = $activity->booking_cutoff_minutes;

        $activity->sessions()->where('starts_at', '>=', now())->get()->each(function ($session) use ($minutes): void {
            $session->update(['booking_cutoff_at' => $minutes === null ? null : $session->starts_at->subMinutes((int) $minutes)]);
        });
    }

    /**
     * Authorise, run in a transaction, and remove any files stored by a save
     * that then failed.
     */
    private function withStorage(Activity $activity, string $what, callable $work): Activity
    {
        $storedPaths = [];
        Gate::authorize('update', $activity);

        $store = function (UploadedFile $file, string $folder) use (&$storedPaths): string {
            $folder .= Media::isVideo($file->getClientOriginalName()) ? '/videos' : '';
            $path = $file->store($folder, 'public');
            if ($path === false) {
                throw new \RuntimeException('Unable to store an uploaded file.');
            }

            return $storedPaths[] = $path;
        };

        try {
            $result = DB::transaction(fn () => $work($store));
            Log::info("Experience {$what} saved.", ['activity_id' => $activity->id]);

            return $result;
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            Log::error("Experience {$what} save failed.", ['activity_id' => $activity->id, 'exception' => $exception->getMessage()]);

            throw $exception;
        }
    }

    /** Stored and wanted values compare equal (null, '' and [] are all "empty"). */
    private function same(mixed $stored, mixed $wanted): bool
    {
        $empty = fn ($value) => $value === null || $value === '' || $value === [];

        if ($empty($stored) || $empty($wanted)) {
            return $empty($stored) && $empty($wanted);
        }
        if (is_array($stored) || is_array($wanted)) {
            return (array) $stored === (array) $wanted;
        }
        if (is_bool($stored) || is_bool($wanted)) {
            return (bool) $stored === (bool) $wanted;
        }
        if (is_numeric($stored) && is_numeric($wanted)) {
            return (float) $stored === (float) $wanted;
        }

        return (string) $stored === (string) $wanted;
    }

    /** A slug no other experience uses (the experience's own slug is kept). */
    private function uniqueSlug(string $wanted, ?Activity $activity = null): string
    {
        $base = Str::slug($wanted) ?: 'experience';
        $slug = $base;
        $n = 1;

        while (Activity::withTrashed()->where('slug', $slug)->when($activity, fn ($q) => $q->whereKeyNot($activity->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
