<?php

namespace App\Modules\Accommodation\Application\Services;

use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Room;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Support\Media;
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
     * Save everything below the hotel's core details: photos, property
     * amenities, and each room type with its qualities, amenities, photos,
     * room inventory and seasonal rates. Used both when a hotel is first set
     * up and whenever the host manages it afterwards.
     *
     * Room types missing from $roomTypes are removed unless they have bookings
     * (the caller validates that first). Rates and inventory are operational,
     * so they are saved without sending a published hotel back to review; the
     * hotel's "from" price is updated quietly for the same reason.
     *
     * @param  array{cover: ?UploadedFile, cover_path: ?string, gallery_paths: array<int, string>, new_gallery: array<int, UploadedFile>}  $media
     * @param  array<int, int>  $amenities
     * @param  array<int, array<string, mixed>>  $roomTypes
     */
    public function saveInventory(Hotel $hotel, array $media, array $amenities, array $roomTypes): Hotel
    {
        return $this->withStorage($hotel, 'inventory', function (callable $store) use ($hotel, $media, $amenities, $roomTypes): Hotel {
            $this->storeMedia($hotel, $media, $store);
            $hotel->amenities()->sync($amenities);
            $this->storeRoomTypes($hotel, $roomTypes, $store);

            return $hotel->fresh(['amenities', 'roomTypes.rooms', 'accommodation']);
        });
    }

    /**
     * Save just the cover photo, cover film and gallery (photos and videos),
     * e.g. as soon as an upload finishes while a hotel is being set up.
     *
     * @param  array{cover: ?UploadedFile, cover_path: ?string, cover_video?: ?UploadedFile, cover_video_path?: ?string, gallery_paths: array<int, string>, new_gallery: array<int, UploadedFile>}  $media
     */
    public function saveMedia(Hotel $hotel, array $media): Hotel
    {
        return $this->withStorage($hotel, 'media', function (callable $store) use ($hotel, $media): Hotel {
            $this->storeMedia($hotel, $media, $store);

            return $hotel->fresh();
        });
    }

    /**
     * Save just the room types (and their rooms and rates).
     *
     * @param  array<int, array<string, mixed>>  $roomTypes
     */
    public function saveRoomTypes(Hotel $hotel, array $roomTypes): Hotel
    {
        return $this->withStorage($hotel, 'room types', function (callable $store) use ($hotel, $roomTypes): Hotel {
            $this->storeRoomTypes($hotel, $roomTypes, $store);

            return $hotel->fresh(['roomTypes.rooms', 'roomTypes.amenities', 'roomTypes.seasonalRates']);
        });
    }

    /**
     * Authorise, run in a transaction, and remove any files stored by a save
     * that then failed.
     */
    private function withStorage(Hotel $hotel, string $what, callable $work): Hotel
    {
        $storedPaths = [];
        Gate::authorize('update', $hotel);

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
            Log::info("Hotel {$what} saved.", ['hotel_id' => $hotel->id]);

            return $result;
        } catch (\Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            Log::error("Hotel {$what} save failed.", ['hotel_id' => $hotel->id, 'exception' => $exception->getMessage()]);

            throw $exception;
        }
    }

    private function storeMedia(Hotel $hotel, array $media, callable $store): void
    {
        // Only touch the columns when they actually change, so an unchanged
        // gallery never sends a live hotel back to review.
        $cover = ($media['cover'] ?? null) instanceof UploadedFile ? $store($media['cover'], 'hotels/covers') : ($media['cover_path'] ?? null);
        $video = ($media['cover_video'] ?? null) instanceof UploadedFile ? $store($media['cover_video'], 'hotels/covers') : ($media['cover_video_path'] ?? null);
        $gallery = array_values(array_merge(
            (array) ($media['gallery_paths'] ?? []),
            array_map(fn (UploadedFile $file) => $store($file, 'hotels/gallery'), array_filter((array) ($media['new_gallery'] ?? []), fn ($file) => $file instanceof UploadedFile))
        ));

        if ($cover !== $hotel->cover_image) {
            $hotel->cover_image = $cover;
        }
        if (array_key_exists('cover_video', $media) || array_key_exists('cover_video_path', $media)) {
            if ($video !== $hotel->cover_video) {
                $hotel->cover_video = $video;
            }
        }
        if ($gallery !== array_values((array) ($hotel->gallery ?? []))) {
            $hotel->gallery = $gallery;
        }
        if ($hotel->isDirty()) {
            $hotel->save();
        }
    }

    private function storeRoomTypes(Hotel $hotel, array $roomTypes, callable $store): void
    {
        $keep = [];
        foreach ($roomTypes as $data) {
            $keep[] = $this->saveRoomType($hotel, $data, $store)->id;
        }

        $hotel->roomTypes()->whereNotIn('id', $keep)->get()->each(function (RoomType $type) {
            $type->seasonalRates()->delete();
            $type->amenities()->detach();
            $type->rooms()->delete();
            $type->delete();
        });

        // The "from" price guests see is the cheapest room type.
        $from = (float) $hotel->roomTypes()->where('price_per_night', '>', 0)->min('price_per_night');
        if ($from > 0 && (float) $hotel->avg_price_per_night !== $from) {
            $hotel->updateQuietly(['avg_price_per_night' => $from]);
            $hotel->accommodation()->update(['avg_price_per_night' => $from]);
        }
    }

    /**
     * Create or update one room type and everything hanging off it.
     */
    private function saveRoomType(Hotel $hotel, array $data, callable $store): RoomType
    {
        $beds = collect($data['beds'] ?? [])
            ->filter(fn ($bed) => isset(RoomType::BED_TYPES[$bed['type'] ?? '']) && (int) ($bed['count'] ?? 0) > 0)
            ->map(fn ($bed) => ['type' => $bed['type'], 'count' => (int) $bed['count']])
            ->values()
            ->all();

        $adults = max(1, (int) ($data['max_adults'] ?? 2));
        $children = max(0, (int) ($data['max_children'] ?? 0));

        $attributes = [
            // Drafts are saved as the host goes, so a name or rate may still be missing.
            'name' => filled($data['name'] ?? null) ? trim($data['name']) : 'Untitled room type',
            'description' => $data['description'] ?? null,
            'price_per_night' => (float) ($data['price_per_night'] ?? 0),
            'currency' => $hotel->currency ?: 'KES',
            'capacity' => $adults + $children,
            'beds' => max(1, (int) collect($beds)->sum('count')),
            'size_sqm' => filled($data['size_sqm'] ?? null) ? (int) $data['size_sqm'] : null,
            'view' => ($data['view'] ?? null) ?: null,
            'bed_configuration' => $beds,
            'max_adults' => $adults,
            'max_children' => $children,
            'breakfast_included' => (bool) ($data['breakfast_included'] ?? false),
            'refundable' => (bool) ($data['refundable'] ?? true),
            'smoking_allowed' => (bool) ($data['smoking_allowed'] ?? false),
            'accessible' => (bool) ($data['accessible'] ?? false),
            'gallery_images' => array_values(array_merge(
                (array) ($data['gallery_paths'] ?? []),
                array_map(fn (UploadedFile $file) => $store($file, 'roomtypes/gallery'), array_filter((array) ($data['new_images'] ?? []), fn ($file) => $file instanceof UploadedFile))
            )),
        ];

        $type = filled($data['id'] ?? null)
            ? $hotel->roomTypes()->findOrFail($data['id'])
            : $hotel->roomTypes()->make();

        $type->fill($attributes)->save();
        $type->amenities()->sync(array_map('intval', (array) ($data['amenities'] ?? [])));

        $this->syncRooms($hotel, $type, $data, collect($beds)->first()['type'] ?? null);

        $type->seasonalRates()->delete();
        foreach ((array) ($data['rates'] ?? []) as $rate) {
            if (blank($rate['start'] ?? null) || blank($rate['end'] ?? null) || (float) ($rate['rate'] ?? 0) <= 0) {
                continue;
            }

            $type->seasonalRates()->create([
                'description' => $rate['label'] ?: 'Seasonal rate',
                'start_date' => $rate['start'],
                'end_date' => $rate['end'],
                'rate' => (float) $rate['rate'],
                'currency' => $hotel->currency ?: 'KES',
                'active' => true,
            ]);
        }

        return $type;
    }

    /**
     * Bring the room type's rooms in line with the host's inventory: keep each
     * listed room's number and status, then add or remove rooms to reach the
     * requested count. New rooms are numbered from `first_room`, skipping any
     * number already used in the hotel.
     */
    private function syncRooms(Hotel $hotel, RoomType $type, array $data, ?string $bedType): void
    {
        $listed = collect($data['units'] ?? [])->keyBy('id');
        $wanted = max(0, (int) ($data['room_count'] ?? $listed->count()));

        $rooms = $type->rooms()->orderBy('room_number')->get();

        foreach ($rooms as $room) {
            $unit = $listed->get($room->id);
            if (! $unit) {
                continue;
            }

            $room->fill([
                'room_number' => (int) $unit['room_number'],
                'status' => in_array($unit['status'] ?? 'available', ['available', 'maintenance', 'out_of_service'], true) ? $unit['status'] : 'available',
                'is_available' => ($unit['status'] ?? 'available') === 'available',
                'capacity' => $type->capacity,
                'beds' => $type->beds,
                'bed_type' => $bedType,
            ])->save();
        }

        // Remove surplus rooms, highest numbers first.
        $surplus = $rooms->count() - $wanted;
        if ($surplus > 0) {
            $rooms->sortByDesc('room_number')->take($surplus)->each->delete();
        }

        $missing = $wanted - min($rooms->count(), $wanted);
        $taken = Room::query()->where('hotel_id', $hotel->id)->pluck('room_number')->all();
        $number = max(1, (int) ($data['first_room'] ?? 0));

        for ($i = 0; $i < $missing; $i++) {
            while (in_array($number, $taken, true)) {
                $number++;
            }

            $type->rooms()->create([
                'hotel_id' => $hotel->id,
                'room_number' => $number,
                'name' => $type->name.' '.$number,
                'capacity' => $type->capacity,
                'beds' => $type->beds,
                'bed_type' => $bedType,
                'status' => 'available',
                'is_available' => true,
            ]);

            $taken[] = $number;
        }
    }
}
