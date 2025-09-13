<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        // Decode gallery JSON if exists
        $gallery = $this->gallery ? json_decode($this->gallery, true) : [];

        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'description' => $this->description,

            // Capacity
            'capacity' => [
                'guests' => $this->capacity,
                'beds'   => $this->beds,
                'bed_type' => $this->bed_type,
            ],

            // Availability
            'is_available' => (bool) $this->is_available,

            // Amenities / features
            'features' => [
                'ac'       => (bool) $this->has_ac,
                'wifi'     => (bool) $this->has_wifi,
                'tv'       => (bool) $this->has_tv,
                'balcony'  => (bool) $this->has_balcony,
            ],

            // Media
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'gallery'   => array_map(fn($img) => asset('storage/' . $img), $gallery),

            // Relations
            'hotel' => [
                'id'   => $this->hotel->id,
                'name' => $this->hotel->name,
            ],

            // Reviews (optional, if loaded)
            'reviews' => \App\Http\Resources\ReviewResource::collection($this->whenLoaded('reviews')),

            // Metadata
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
