<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Villa;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VillaCreator
{
    /**
     * Finalize and publish a villa.
     *
     * @param  mixed  $coverImage  (UploadedFile|string|null)
     * @param  array  $gallery  (array of UploadedFiles or paths)
     * @param  array  $amenities  (IDs of selected amenities)
     *
     * @throws Throwable
     */
    public function createVilla(?Villa $draft, $coverImage, array $gallery, array $amenities): Villa
    {
        $provider = Auth::user()?->provider;
        if (! $provider || ! Auth::user()->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        $storedPaths = [];

        try {
            return DB::transaction(function () use ($draft, $coverImage, $gallery, $amenities, $provider, &$storedPaths): Villa {
                $villa = $draft ?? new Villa;
                Gate::authorize($draft ? 'update' : 'create', $draft ?? Villa::class);

                $villa->provider_id = $provider->id;
                $villa->slug = $this->generateSlug($villa->name, $villa);

                if ($coverImage) {
                    if (is_string($coverImage)) {
                        $villa->cover_image = $coverImage;
                    } else {
                        $path = $coverImage->store('villas/covers', 'public');
                        if ($path === false) {
                            throw new \RuntimeException('Unable to store the villa cover image.');
                        }
                        $storedPaths[] = $path;
                        $villa->cover_image = $path;
                    }
                }

                if ($gallery !== []) {
                    $galleryPaths = [];
                    foreach ($gallery as $image) {
                        if (is_string($image)) {
                            $path = $image;
                        } else {
                            $path = $image->store('villas/gallery', 'public');
                            if ($path === false) {
                                throw new \RuntimeException('Unable to store a villa gallery image.');
                            }
                            $storedPaths[] = $path;
                        }
                        $galleryPaths[] = $path;
                    }
                    $villa->gallery = $galleryPaths;
                }

                $villa->is_active = true;
                $villa->is_verified = false;
                $villa->save();
                $villa->amenities()->sync($amenities);

                return $villa->fresh(['accommodation']);
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }
    }

    /**
     * Save or update a villa draft.
     *
     * @throws \Throwable
     */
    public function saveDraft(array $data, ?Villa $draft = null): Villa
    {
        $provider = Auth::user()?->provider;
        if (! $provider || ! Auth::user()->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        return DB::transaction(function () use ($data, $draft, $provider): Villa {
            $destinationId = $data['destination_id'] ?? null;
            unset($data['destination_id'], $data['provider_id']);
            $data['provider_id'] = $provider->id;

            // Normalize / sanitize input
            $data['name'] = trim($data['name'] ?? 'Untitled Villa');
            $data['slug'] = $this->generateSlug($data['name'], $draft);

            // Ensure default structure for policies & metadata
            $data['policies'] = $data['policies'] ?? [];
            $data['meta_data'] = $data['meta_data'] ?? [];

            // Ensure defaults for features
            $data['bedrooms'] = $data['bedrooms'] ?? 1;
            $data['bathrooms'] = $data['bathrooms'] ?? 1;
            $data['max_guests'] = $data['max_guests'] ?? 2;
            $data['has_private_pool'] = $data['has_private_pool'] ?? false;

            // If updating draft, update it, otherwise create new
            if ($draft) {
                Gate::authorize('update', $draft);
                $draft->fill($data);
                $draft->save();

                $villa = $draft;
            } else {
                $villa = Villa::create($data);
            }

            if ($destinationId !== null) {
                $villa->accommodation()->update(['destination_id' => $destinationId]);
            }

            return $villa->fresh(['accommodation']);
        });
    }

    /**
     * Generate a unique slug.
     */
    protected function generateSlug(string $name, ?Villa $draft = null): string
    {
        $baseSlug = Str::slug($name);

        // If same draft, keep slug
        if ($draft && $draft->slug && Str::slug($draft->name) === $baseSlug) {
            return $draft->slug;
        }

        $slug = $baseSlug;
        $count = 1;

        while (Villa::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count++;
        }

        return $slug;
    }
}
