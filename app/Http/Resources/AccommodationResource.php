<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug ?? null,
            'description'         => $this->description,

            // Relations
            'destination'         => [
                'id'   => $this->destination?->id,
                'name' => $this->destination?->name,
            ],
            'provider'            => $this->provider ? [
                'id'   => $this->provider->id,
                'name' => $this->provider->name,
            ] : null,

            // Polymorphic target
            'type'                => $this->accommodation_type,
            'accommodation_id'    => $this->accommodation_id,

            // Attributes
            'is_featured'         => (bool) $this->is_featured,
            'avg_price_per_night' => (float) $this->avg_price_per_night,
            'avg_rating'          => (float) $this->avg_rating,
            'reviews_count'       => $this->reviews_count,

            'cover_image'         => $this->cover_image ? asset('storage/' . $this->cover_image) : null,

            // Timestamps
            'created_at'          => $this->created_at?->toDateTimeString(),
            'updated_at'          => $this->updated_at?->toDateTimeString(),
        ];
    }
}
