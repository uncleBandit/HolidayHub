<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Villa;
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
 * Saves a villa while its host sets it up and whenever they manage it later.
 *
 * The villa's own details (name, story, address, house rules, photos) sync to
 * the canonical listing and send a published villa back to review when they
 * change. Rates, minimum stay and seasons are operational, so they are saved
 * quietly and go live straight away, as they do for hotels.
 */
class VillaCreator
{
    /**
     * Create or update the villa row itself (a draft until submitted).
     *
     * `meta_data` is merged into what is stored, so keys this form doesn't
     * know about are kept.
     */
    public function saveDraft(array $data, ?Villa $villa = null): Villa
    {
        $provider = Auth::user()?->provider;
        if (! $provider || ! Auth::user()->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        return DB::transaction(function () use ($data, $villa, $provider): Villa {
            $destinationId = $data['destination_id'] ?? null;
            unset($data['destination_id'], $data['provider_id'], $data['avg_price_per_night'], $data['min_nights']);

            $data['name'] = trim((string) ($data['name'] ?? '')) ?: 'Untitled villa';
            $data['slug'] = $this->uniqueSlug(filled($data['slug'] ?? null) ? $data['slug'] : $data['name'], $villa);

            if (array_key_exists('meta_data', $data)) {
                $data['meta_data'] = array_replace((array) ($villa?->meta_data ?? []), (array) $data['meta_data']);
            }

            if ($villa) {
                Gate::authorize('update', $villa);
                $villa->fill($data);
                if ($villa->isDirty()) {
                    $villa->save();
                }
            } else {
                Gate::authorize('create', Villa::class);
                $villa = new Villa($data);
                $villa->provider_id = $provider->id;
                $villa->is_active = true;
                $villa->is_verified = false;
                $villa->setup_step = $data['setup_step'] ?? 1;
                $villa->save();
            }

            if ($destinationId !== null) {
                $villa->accommodation()->update(['destination_id' => $destinationId]);
            }

            Log::info('Villa draft saved.', ['villa_id' => $villa->id, 'provider_id' => $provider->id]);

            return $villa->fresh(['accommodation']);
        });
    }

    /**
     * Save the cover photo, cover film and gallery (photos and films).
     *
     * @param  array{cover?: ?UploadedFile, cover_path?: ?string, cover_video?: ?UploadedFile, cover_video_path?: ?string, gallery_paths?: array<int, string>, new_gallery?: array<int, UploadedFile>}  $media
     */
    public function saveMedia(Villa $villa, array $media): Villa
    {
        return $this->withStorage($villa, 'media', function (callable $store) use ($villa, $media): Villa {
            // Only touch the columns when they actually change, so an unchanged
            // gallery never sends a live villa back to review.
            $cover = ($media['cover'] ?? null) instanceof UploadedFile ? $store($media['cover'], 'villas/covers') : ($media['cover_path'] ?? null);
            $video = ($media['cover_video'] ?? null) instanceof UploadedFile ? $store($media['cover_video'], 'villas/covers') : ($media['cover_video_path'] ?? null);
            $gallery = array_values(array_merge(
                (array) ($media['gallery_paths'] ?? []),
                array_map(fn (UploadedFile $file) => $store($file, 'villas/gallery'), array_filter((array) ($media['new_gallery'] ?? []), fn ($file) => $file instanceof UploadedFile))
            ));

            if ($cover !== $villa->cover_image) {
                $villa->cover_image = $cover;
            }
            if ($video !== $villa->cover_video) {
                $villa->cover_video = $video;
            }
            if ($gallery !== array_values((array) ($villa->gallery ?? []))) {
                $villa->gallery = $gallery;
            }
            if ($villa->isDirty()) {
                $villa->save();
            }

            return $villa->fresh();
        });
    }

    /**
     * Save the nightly rate, minimum stay and seasonal rates without sending
     * a published villa back to review.
     *
     * @param  array<int, array{label?: ?string, start?: ?string, end?: ?string, rate?: mixed}>  $seasons
     */
    public function savePricing(Villa $villa, mixed $nightly, int $minNights, array $seasons): Villa
    {
        Gate::authorize('update', $villa);

        return DB::transaction(function () use ($villa, $nightly, $minNights, $seasons): Villa {
            $changes = [];
            $price = filled($nightly) && (float) $nightly > 0 ? round((float) $nightly, 2) : null;

            if ($price !== null && (float) $villa->avg_price_per_night !== $price) {
                $changes['avg_price_per_night'] = $price;
            }
            if (max(1, $minNights) !== (int) $villa->min_nights) {
                $changes['min_nights'] = max(1, $minNights);
            }

            if ($changes !== []) {
                $villa->updateQuietly($changes);

                if (isset($changes['avg_price_per_night'])) {
                    $villa->accommodation()->update(['avg_price_per_night' => $price]);
                }
            }

            $currency = $villa->getCurrency();
            $wanted = collect($seasons)
                ->filter(fn ($season) => filled($season['start'] ?? null) && filled($season['end'] ?? null) && (float) ($season['rate'] ?? 0) > 0)
                ->map(fn ($season) => [
                    'description' => filled($season['label'] ?? null) ? trim($season['label']) : 'Seasonal rate',
                    'start_date' => (string) $season['start'],
                    'end_date' => (string) $season['end'],
                    'rate' => round((float) $season['rate'], 2),
                    'currency' => $currency,
                ])
                ->values();

            $stored = $villa->seasonalRates()->orderBy('start_date')->orderBy('id')->get()
                ->map(fn ($rate) => [
                    'description' => (string) $rate->description,
                    'start_date' => \Carbon\Carbon::parse($rate->start_date)->format('Y-m-d'),
                    'end_date' => \Carbon\Carbon::parse($rate->end_date)->format('Y-m-d'),
                    'rate' => round((float) $rate->rate, 2),
                    'currency' => (string) $rate->currency,
                ])->values();

            // Rewrite the seasons only when they changed.
            if ($wanted->sortBy('start_date')->values()->all() !== $stored->all()) {
                $villa->seasonalRates()->delete();
                foreach ($wanted as $season) {
                    $villa->seasonalRates()->create($season + ['active' => true]);
                }
            }

            Log::info('Villa pricing saved.', ['villa_id' => $villa->id]);

            return $villa->fresh(['accommodation', 'seasonalRates']);
        });
    }

    /**
     * Authorise, run in a transaction, and remove any files stored by a save
     * that then failed.
     */
    private function withStorage(Villa $villa, string $what, callable $work): Villa
    {
        $storedPaths = [];
        Gate::authorize('update', $villa);

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
            Log::info("Villa {$what} saved.", ['villa_id' => $villa->id]);

            return $result;
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            Log::error("Villa {$what} save failed.", ['villa_id' => $villa->id, 'exception' => $exception->getMessage()]);

            throw $exception;
        }
    }

    /**
     * A slug no other villa uses (the villa's own slug is kept as it is).
     */
    private function uniqueSlug(string $wanted, ?Villa $villa = null): string
    {
        $base = Str::slug($wanted) ?: 'villa';
        $slug = $base;
        $n = 1;

        while (Villa::withoutGlobalScopes()->where('slug', $slug)->when($villa, fn ($q) => $q->whereKeyNot($villa->id))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
