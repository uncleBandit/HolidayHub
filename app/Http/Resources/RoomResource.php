<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'slug'     => $this->slug ?? null, // SEO-friendly

            // Descriptions
            'short_description' => $this->short_description,
            'description'       => $this->description,
            'language'          => $this->language ?? 'en',

            // Capacity
            'capacity' => [
                'adults'   => $this->capacity_adults ?? 2,
                'children' => $this->capacity_children ?? 0,
                'beds'     => $this->beds ?? '1 Queen',
            ],

            // Pricing
            'base_price'     => (float) $this->base_price_per_night,
            'currency'       => $this->currency ?? 'USD',
            'dynamic_pricing'=> [
                'current_price' => $this->current_price ?? $this->base_price_per_night,
                'discount'      => $this->discount ?? null,
                'seasonal_rate' => $this->seasonal_rate ?? null,
            ],

            // Availability (context-aware if dates passed in query)
            'availability' => [
                'is_available'  => $this->when(isset($this->availability), $this->availability['status'] ?? true),
                'next_available'=> $this->when(isset($this->availability), $this->availability['next_date'] ?? null),
            ],

            // Amenities
            'amenities' => $this->amenities ?? [], // e.g. ["WiFi", "TV", "Air Conditioning"]

            // Media
            'images' => $this->whenLoaded('media', function () {
                return $this->media->map(fn($m) => [
                    'url'  => asset('storage/' . $m->path),
                    'type' => $m->type,
                ]);
            }),
            'virtual_tour' => $this->virtual_tour_url ?? null, // 3D / VR link

            // Reviews
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),

            // Hotel context
            'hotel' => [
                'id'   => $this->hotel->id,
                'name' => $this->hotel->name,
            ],

            // Smart recommendations (future-ready AI field)
            'tags' => $this->tags ?? ['family-friendly', 'quiet area', 'sea view'],

            // Metadata
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
