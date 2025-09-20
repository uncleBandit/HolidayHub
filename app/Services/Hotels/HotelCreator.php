<?php

namespace App\Services\Hotels;

use App\Models\Hotel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;


class HotelCreator
{
    public function saveDraft(array $data, ?Hotel $draftHotel = null): Hotel
    {
        if ($draftHotel) {
            $draftHotel->update($data);
            return $draftHotel;
        }
        return Hotel::create(array_merge($data, [
            'provider_id' => $data['provider_id'] ?? auth()->id(),
        ]));
    }

    public function createHotel(Hotel $draftHotel, $coverImage, $gallery, array $amenities): Hotel
    {
        // Handle cover
        if ($coverImage) {
            $path = $coverImage->store('hotels/covers', 'public');
            $draftHotel->images()->create([
                'path' => $path,
                'type' => 'cover',
            ]);
        }

        // Handle gallery
        if (!empty($gallery)) {
            foreach ($gallery as $img) {
                $path = $img->store('hotels/gallery', 'public');
                $draftHotel->images()->create([
                    'path' => $path,
                    'type' => 'gallery',
                ]);
            }
        }

        // Sync amenities
        if (!empty($amenities)) {
            $draftHotel->amenities()->sync($amenities);
        }

        return $draftHotel;
    }


    /**
     * Create hotel and its room types, including media and amenities.
     */
    public function createHotelWithRoomTypes(
        Hotel $draftHotel,
        ?UploadedFile $coverImage,
        array $gallery,
        array $amenities,
        array $roomTypes
    ): Hotel {
        // Save cover image
        if ($coverImage) {
            $draftHotel->cover_image = $coverImage->store('hotels/covers', 'public');
        }

        // Save gallery images
        if (!empty($gallery)) {
            $draftHotel->gallery = collect($gallery)
                ->map(fn($img) => $img->store('hotels/gallery', 'public'))
                ->toArray();
        }

        $draftHotel->save();

        // Sync amenities
        if (!empty($amenities)) {
            $draftHotel->amenities()->sync($amenities);
        }

        // Save room types
        foreach ($roomTypes as $data) {
            $rt = $draftHotel->roomTypes()->updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            // Save room type gallery images if uploaded
            if (!empty($data['gallery_images'])) {
                $rt->gallery_images = collect($data['gallery_images'])
                    ->map(fn($img) => $img instanceof UploadedFile ? $img->store('roomtypes/gallery', 'public') : $img)
                    ->toArray();
                $rt->save();
            }
        }

        return $draftHotel;
    }
}
