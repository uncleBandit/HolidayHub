<?php

namespace App\Modules\Accommodation\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->accommodation?->name,
            'slug' => $this->accommodation?->slug,
            'description' => $this->accommodation?->description,

            // Location
            'location' => [
                'address' => $this->accommodation?->address,
                'city' => $this->accommodation?->city,
                'country' => $this->accommodation?->country,
                'latitude' => $this->accommodation?->latitude,
                'longitude' => $this->accommodation?->longitude,
            ],

            // Features
            'stars' => $this->stars,
            'is_featured' => (bool) $this->accommodation?->is_featured,
            'amenities' => $this->amenities,
            'policies' => $this->policies,

            // Media
            'cover_image' => $this->cover_image ? asset('storage/'.$this->cover_image) : null,
            'gallery' => $this->gallery
                                ? collect($this->gallery)->map(fn ($img) => asset('storage/'.$img))
                                : [],

            // Pricing & Ratings
            'avg_price_per_night' => $this->accommodation?->avg_price_per_night,
            'avg_rating' => $this->accommodation?->avg_rating,
            'reviews_count' => $this->accommodation?->reviews_count,

            // Relations
            'provider_id' => $this->accommodation?->provider_id,
            'destination_id' => $this->accommodation?->destination_id,
            'publication_status' => $this->accommodation?->status?->value,

            // Nested resources (only if eager-loaded)
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),

            // System fields
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
