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
            'description' => $this->description,
            'location'    => $this->location,
            'price_per_night' => $this->price_per_night,
            'rating'      => $this->rating,
            'amenities'   => $this->amenities,
            'image_url'   => $this->image ? asset('storage/' . $this->image) : null,
            'rooms'       => RoomResource::collection($this->whenLoaded('rooms')),
            'reviews'     => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
