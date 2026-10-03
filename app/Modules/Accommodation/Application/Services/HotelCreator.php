<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\RoomType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HotelCreator
{
    public function saveDraft(array $data, ?Hotel $draftHotel = null): Hotel
    {
        $provider = Auth::user()?->provider;
        if (! $provider || ! Auth::user()->hasRole('provider')) {
            throw new AuthorizationException('An authenticated provider account is required.');
        }

        return DB::transaction(function () use ($data, $draftHotel, $provider): Hotel {
            $destinationId = $data['destination_id'] ?? null;
            unset($data['destination_id'], $data['provider_id']);
            $data['provider_id'] = $provider->id;

            if ($draftHotel) {
                Gate::authorize('update', $draftHotel);
                $draftHotel->update($data);
                $hotel = $draftHotel;
            } else {
                $hotel = Hotel::create($data);
            }

            if ($destinationId !== null) {
                $hotel->accommodation()->update(['destination_id' => $destinationId]);
            }

            Log::info('Hotel draft saved.', ['hotel_id' => $hotel->id, 'provider_id' => $provider->id]);

            return $hotel->fresh(['accommodation']);
        });
    }

    /**
     * Store a provider's hotel details and related inventory as a draft.
     *
     * @param  array<int, UploadedFile|string>  $gallery
     * @param  array<int, int>  $amenities
     * @param  array<int, array<string, mixed>>  $roomTypes
     */
    public function createHotelWithRoomTypes(
        Hotel $draftHotel,
        ?UploadedFile $coverImage,
        array $gallery,
        array $amenities,
        array $roomTypes
    ): Hotel {
        $storedPaths = [];

        try {
            Gate::authorize('update', $draftHotel);

            return DB::transaction(function () use ($draftHotel, $coverImage, $gallery, $amenities, $roomTypes, &$storedPaths): Hotel {
                if ($coverImage instanceof UploadedFile) {
                    $path = $coverImage->store('hotels/covers', 'public');
                    if ($path === false) {
                        throw new \RuntimeException('Unable to store the hotel cover image.');
                    }
                    $storedPaths[] = $path;
                    $draftHotel->cover_image = $path;
                }

                if ($gallery !== []) {
                    $storedGallery = [];
                    foreach ($gallery as $image) {
                        if ($image instanceof UploadedFile) {
                            $path = $image->store('hotels/gallery', 'public');
                            if ($path === false) {
                                throw new \RuntimeException('Unable to store a hotel gallery image.');
                            }
                            $storedPaths[] = $path;
                        } else {
                            $path = $image;
                        }
                        $storedGallery[] = $path;
                    }

                    $draftHotel->gallery = $storedGallery;
                }

                $draftHotel->save();

                $draftHotel->amenities()->sync($amenities);

                $usedSlugs = [];
                foreach ($roomTypes as $data) {
                    $baseSlug = Str::slug($data['slug'] ?? $data['name'] ?? 'room');
                    $slug = $baseSlug;
                    $suffix = 1;

                    while (
                        in_array($slug, $usedSlugs, true)
                        || RoomType::query()->where('slug', $slug)->where('hotel_id', '!=', $draftHotel->id)->exists()
                    ) {
                        $slug = "{$baseSlug}-{$suffix}";
                        $suffix++;
                    }
                    $usedSlugs[] = $slug;

                    $roomType = $draftHotel->roomTypes()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $data['name'] ?? 'Unnamed Room',
                            'description' => $data['description'] ?? null,
                            'price_per_night' => $data['price_per_night'] ?? 0,
                            'capacity' => $data['capacity'] ?? 1,
                            'beds' => $data['beds'] ?? 1,
                        ]
                    );

                    if (! empty($data['gallery_images'])) {
                        $roomTypeGallery = [];
                        foreach ($data['gallery_images'] as $image) {
                            if ($image instanceof UploadedFile) {
                                $path = $image->store('roomtypes/gallery', 'public');
                                if ($path === false) {
                                    throw new \RuntimeException('Unable to store a room type gallery image.');
                                }
                                $storedPaths[] = $path;
                            } else {
                                $path = $image;
                            }
                            $roomTypeGallery[] = $path;
                        }

                        $roomType->gallery_images = $roomTypeGallery;
                        $roomType->save();
                    }
                }

                Log::info('Hotel draft and room types saved.', [
                    'hotel_id' => $draftHotel->id,
                    'provider_id' => $draftHotel->accommodation?->provider_id,
                ]);

                return $draftHotel->fresh(['amenities', 'roomTypes', 'accommodation']);
            });
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            Log::error('Hotel draft save failed.', [
                'hotel_id' => $draftHotel->id ?? null,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
