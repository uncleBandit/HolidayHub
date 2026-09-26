<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Villa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($draft, $coverImage, $gallery, $amenities) {
            // Ensure draft exists
            $villa = $draft ?? new Villa;

            // Ensure provider is set
            $villa->provider_id = $villa->provider_id ?? Auth::user()->provider->id;

            // Ensure slug is unique
            $villa->slug = $this->generateSlug($villa->name, $villa);

            // ✅ Handle media uploads
            if ($coverImage) {
                $villa->cover_image = is_string($coverImage)
                    ? $coverImage
                    : $coverImage->store('villas/covers', 'public');
            }

            if (! empty($gallery)) {
                $galleryPaths = [];
                foreach ($gallery as $image) {
                    $galleryPaths[] = is_string($image)
                        ? $image
                        : $image->store('villas/gallery', 'public');
                }
                $villa->gallery = $galleryPaths;
            }

            // ✅ Mark as active and verified when publishing
            $villa->is_active = true;
            $villa->is_verified = false; // keep manual verification by admin if required

            $villa->save();

            // ✅ Sync amenities (many-to-many)
            if (! empty($amenities)) {
                $villa->amenities()->sync($amenities);
            }

            return $villa;
        });
    }

    /**
     * Save or update a villa draft.
     *
     * @throws \Throwable
     */
    public function saveDraft(array $data, ?Villa $draft = null): Villa
    {
        return DB::transaction(function () use ($data, $draft) {
            // Provider must always be set
            $data['provider_id'] = $data['provider_id'] ?? Auth::user()->provider->id;

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
                $draft->fill($data);
                $draft->save();

                return $draft;
            }

            return Villa::create($data);
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
