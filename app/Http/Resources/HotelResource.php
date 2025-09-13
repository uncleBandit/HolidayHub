<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,

            // Location
            'location' => [
                'address'   => $this->address,
                'city'      => $this->city,
                'country'   => $this->country,
                'latitude'  => $this->latitude,
                'longitude' => $this->longitude,
            ],

            // Features
            'stars'       => $this->stars,
            'is_featured' => (bool) $this->is_featured,
            'amenities'   => $this->amenities,
            'policies'    => $this->policies,

            // Media
            'cover_image' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
            'gallery'     => $this->gallery
                                ? collect($this->gallery)->map(fn($img) => asset('storage/' . $img))
                                : [],

            // Pricing & Ratings
            'avg_price_per_night' => $this->avg_price_per_night,
            'avg_rating'          => $this->avg_rating,
            'reviews_count'       => $this->reviews_count,

            // Relations
            'provider_id'    => $this->provider_id,
            'destination_id' => $this->destination_id,

            // Nested resources (only if eager-loaded)
            'rooms'   => RoomResource::collection($this->whenLoaded('rooms')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),

            // System fields
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
