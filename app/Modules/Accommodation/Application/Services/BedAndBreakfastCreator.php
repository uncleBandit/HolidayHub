<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Support\Media;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Saves a bed & breakfast as its host sets it up and, later, manages it:
 * the house itself (a draft until submitted), its photos and films, and its
 * nightly rate and seasons.
 *
 * Rates are operational, so they are saved without sending a published B&B
 * back to review (the same way HotelCreator keeps a hotel's "from" price).
 */
class BedAndBreakfastCreator
{
    public function saveDraft(array $data, ?BedAndBreakfast $draft = null): BedAndBreakfast
    {
        $provider = Auth::user()?->provider;
        if (! $provider || ! Auth::user()->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        return DB::transaction(function () use ($data, $draft, $provider): BedAndBreakfast {
            $destinationId = $data['destination_id'] ?? null;
            unset($data['destination_id'], $data['provider_id']);

            if ($draft) {
                Gate::authorize('update', $draft);
                $draft->update($data);
                $bnb = $draft;
            } else {
                $bnb = BedAndBreakfast::create($data + ['provider_id' => $provider->id]);
            }

            if ($destinationId !== null) {
                $bnb->accommodation()->update(['destination_id' => $destinationId]);
            }

            Log::info('B&B draft saved.', ['bnb_id' => $bnb->id, 'provider_id' => $provider->id]);

            return $bnb->fresh(['accommodation']);
        });
    }

    /**
     * Save the cover photo, cover film and gallery (photos and films).
     *
     * @param  array{cover: ?UploadedFile, cover_path: ?string, cover_video?: ?UploadedFile, cover_video_path?: ?string, gallery_paths: array<int, string>, new_gallery: array<int, UploadedFile>}  $media
     */
    public function saveMedia(BedAndBreakfast $bnb, array $media): BedAndBreakfast
    {
        return $this->withStorage($bnb, 'media', function (callable $store) use ($bnb, $media): BedAndBreakfast {
            // Only touch the columns when they actually change, so an unchanged
            // gallery never sends a live B&B back to review.
            $cover = ($media['cover'] ?? null) instanceof UploadedFile ? $store($media['cover'], 'bnb/covers') : ($media['cover_path'] ?? null);
            $video = ($media['cover_video'] ?? null) instanceof UploadedFile ? $store($media['cover_video'], 'bnb/covers') : ($media['cover_video_path'] ?? null);
            $gallery = array_values(array_merge(
                (array) ($media['gallery_paths'] ?? []),
                array_map(fn (UploadedFile $file) => $store($file, 'bnb/gallery'), array_filter((array) ($media['new_gallery'] ?? []), fn ($file) => $file instanceof UploadedFile))
            ));

            if ($cover !== $bnb->cover_image) {
                $bnb->cover_image = $cover;
            }
            if (array_key_exists('cover_video', $media) || array_key_exists('cover_video_path', $media)) {
                if ($video !== $bnb->cover_video) {
                    $bnb->cover_video = $video;
                }
            }
            if ($gallery !== array_values((array) ($bnb->gallery ?? []))) {
                $bnb->gallery = $gallery;
            }
            if ($bnb->isDirty()) {
                $bnb->save();
            }

            return $bnb->fresh();
        });
    }

    /**
     * Save the nightly rate per room and the seasonal rates.
     *
     * Seasons live in seasonal_rates (what the PricingEngine reads); the legacy
     * `seasonal_pricing` JSON column is left as it is and no longer written.
     *
     * @param  array<int, array{label?: ?string, start?: ?string, end?: ?string, rate?: mixed}>  $seasons
     */
    public function savePricing(BedAndBreakfast $bnb, mixed $pricePerNight, array $seasons): BedAndBreakfast
    {
        return $this->withStorage($bnb, 'pricing', function () use ($bnb, $pricePerNight, $seasons): BedAndBreakfast {
            $currency = $bnb->getCurrency();
            $price = filled($pricePerNight) ? round((float) $pricePerNight, 2) : null;

            // Quietly: a rate change must not send a live listing back to review.
            if ($price !== ($bnb->price_per_night === null ? null : (float) $bnb->price_per_night)) {
                $bnb->updateQuietly(['price_per_night' => $price]);
                $bnb->accommodation()->update(['avg_price_per_night' => $price]);
            }

            $wanted = collect($seasons)
                ->filter(fn ($season) => filled($season['start'] ?? null) && filled($season['end'] ?? null) && (float) ($season['rate'] ?? 0) > 0)
                ->map(fn ($season) => [
                    'description' => trim((string) ($season['label'] ?? '')) ?: 'Seasonal rate',
                    'start_date' => $season['start'],
                    'end_date' => $season['end'],
                    'rate' => round((float) $season['rate'], 2),
                    'currency' => $currency,
                    'active' => true,
                ])
                ->values();

            $current = $bnb->seasonalRates()->orderBy('start_date')->orderBy('id')->get()->map(fn ($rate) => [
                'description' => (string) $rate->description,
                'start_date' => \Carbon\Carbon::parse($rate->start_date)->format('Y-m-d'),
                'end_date' => \Carbon\Carbon::parse($rate->end_date)->format('Y-m-d'),
                'rate' => round((float) $rate->rate, 2),
                'currency' => (string) $rate->currency,
                'active' => (bool) $rate->active,
            ])->values();

            $normalised = $wanted->map(fn ($s) => array_merge($s, [
                'start_date' => \Carbon\Carbon::parse($s['start_date'])->format('Y-m-d'),
                'end_date' => \Carbon\Carbon::parse($s['end_date'])->format('Y-m-d'),
            ]))->sortBy('start_date')->values();

            if ($normalised->all() != $current->sortBy('start_date')->values()->all()) {
                $bnb->seasonalRates()->delete();
                foreach ($normalised as $season) {
                    $bnb->seasonalRates()->create($season);
                }
            }

            return $bnb->fresh(['seasonalRates', 'accommodation']);
        });
    }

    /**
     * Authorise, run in a transaction, and remove any files stored by a save
     * that then failed.
     */
    private function withStorage(BedAndBreakfast $bnb, string $what, callable $work): BedAndBreakfast
    {
        $storedPaths = [];
        Gate::authorize('update', $bnb);

        $store = function (UploadedFile $file, string $folder) use (&$storedPaths): string {
            // Videos and photos live side by side; keep them in their own folders.
            $folder .= Media::isVideo($file->getClientOriginalName()) ? '/videos' : '';
            $path = $file->store($folder, 'public');
            if ($path === false) {
                throw new \RuntimeException('Unable to store an uploaded file.');
            }

            return $storedPaths[] = $path;
        };

        try {
            $result = DB::transaction(fn () => $work($store));
            Log::info("B&B {$what} saved.", ['bnb_id' => $bnb->id]);

            return $result;
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            Log::error("B&B {$what} save failed.", ['bnb_id' => $bnb->id, 'exception' => $exception->getMessage()]);

            throw $exception;
        }
    }
}
