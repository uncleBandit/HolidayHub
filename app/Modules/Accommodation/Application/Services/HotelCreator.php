<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Hotel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HotelCreator
{
    /**
     * Save a draft hotel (incomplete hotel record).
     */
    public function saveDraft(array $data, ?Hotel $draftHotel = null): Hotel
    {
        try {
            if ($draftHotel) {
                $draftHotel->update($data);
                Log::info("Draft hotel [{$draftHotel->id}] updated successfully.");

                return $draftHotel;
            }

            $hotel = Hotel::create(array_merge($data, [
                'provider_id' => $data['provider_id'] ?? Auth::id(),
            ]));

            Log::info("Draft hotel [{$hotel->id}] created successfully.");

            return $hotel;

        } catch (\Throwable $e) {
            Log::error('Hotel draft save failed: '.$e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Publish a hotel (with cover, gallery, amenities, and room types).
     */
    public function createHotelWithRoomTypes(
        Hotel $draftHotel,
        ?UploadedFile $coverImage,
        array $gallery,
        array $amenities,
        array $roomTypes
    ): Hotel {
        try {
            // --- Cover Image ---
            if ($coverImage instanceof UploadedFile) {
                $path = $coverImage->store('hotels/covers', 'public');
                $draftHotel->cover_image = $path; // ✅ Clean public URL
                Log::info("Cover image stored for hotel [{$draftHotel->id}].");
            }

            // --- Gallery Images ---
            if (! empty($gallery)) {
                $storedGallery = collect($gallery)->map(function ($img) {
                    if ($img instanceof UploadedFile) {
                        return $img->store('hotels/gallery', 'public'); // ✅ store relative path
                    }

                    return $img;
                })->toArray();

                $draftHotel->gallery = $storedGallery;
                Log::info("Gallery images stored for hotel [{$draftHotel->id}].", [
                    'count' => count($storedGallery),
                ]);
            }

            $draftHotel->save();

            // --- Amenities ---
            if (! empty($amenities)) {
                $draftHotel->amenities()->sync($amenities);
                Log::info("Amenities synced for hotel [{$draftHotel->id}].", [
                    'amenities' => $amenities,
                ]);
            }

            // --- Room Types ---
            foreach ($roomTypes as $index => $data) {
                // Always start from provided slug or name
                $baseSlug = ! empty($data['slug'])
                    ? Str::slug($data['slug'])
                    : Str::slug($data['name'] ?? 'room');

                $slug = $baseSlug;
                $i = 1;

                // Ensure global uniqueness (not just per hotel)
                while (\App\Modules\Accommodation\Domain\Models\RoomType::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$i}";
                    $i++;
                }

                $data['slug'] = $slug;

                // Create or update the room type
                $rt = $draftHotel->roomTypes()->updateOrCreate(
                    ['slug' => $data['slug']], // ✅ always unique now
                    [
                        'name' => $data['name'] ?? 'Unnamed Room',
                        'price_per_night' => $data['price_per_night'] ?? 0,
                        'capacity' => $data['capacity'] ?? 1,
                        'beds' => $data['beds'] ?? 1,
                    ]
                );

                // Handle gallery images for this room type
                if (! empty($data['gallery_images'])) {
                    $rt->gallery_images = collect($data['gallery_images'])->map(function ($img) {
                        return $img instanceof UploadedFile
                            ? $img->store('roomtypes/gallery', 'public') // ✅ store relative path
                            : $img;
                    })->toArray();

                    $rt->save();
                }

                Log::info("Room type [{$rt->id}] saved for hotel [{$draftHotel->id}].");
            }

            Log::info("Hotel [{$draftHotel->id}] published successfully.");

            return $draftHotel->fresh(['amenities', 'roomTypes']);
        } catch (\Throwable $e) {
            Log::error('Hotel publish failed: '.$e->getMessage(), [
                'hotel_id' => $draftHotel->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
