<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image_url'   => $this->image_url ? asset('storage/' . $this->image_url) : null,

            // Pricing
            'price' => [
                'original'   => $this->price,
                'currency'   => 'USD', // or derive dynamically if you add multi-currency later
                'discount_percent' => $this->discount_percent,
                'discounted_price' => $this->price - ($this->price * ($this->discount_percent / 100)),
            ],

            // Relations
            'destination_id' => $this->destination_id,
            'hotel_id'       => $this->hotel_id,

            // Date range
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,

            // Flags
            'is_featured' => (bool) $this->is_featured,

            // System fields
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
