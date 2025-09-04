<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'description'   => Str::limit(strip_tags($this->description), 300), // preview-friendly
            'full_description' => $this->description,

            // Location
            'destination'   => $this->destination,
            'country'       => $this->country,

            // Pricing
            'price'         => (float) $this->price,
            'currency'      => $this->currency,
            'discount'      => $this->discount ? (float) $this->discount : null,
            'final_price'   => $this->discount
                                ? (float) ($this->price * (1 - $this->discount / 100))
                                : (float) $this->price,

            // Duration & availability
            'duration_days' => $this->duration_days,
            'available_from'=> $this->available_from?->toDateString(),
            'available_to'  => $this->available_to?->toDateString(),
            'is_available'  => $this->available_from && $this->available_to
                                ? now()->between($this->available_from, $this->available_to)
                                : true,

            // Media
            'cover_image'   => $this->images[0] ?? $this->image_url ?? null,
            'images'        => $this->images ?? [],

            // Tags for filtering
            'tags'          => $this->tags ?? [],

            // Status
            'status'        => $this->status,

            // Relations
            'provider'      => [
                'id'    => $this->provider?->id,
                'name'  => $this->provider?->name,
            ],
            'agent'         => [
                'id'    => $this->agent?->id,
                'name'  => $this->agent?->name,
            ],

            // Metadata
            'created_at'    => $this->created_at->toDateTimeString(),
            'updated_at'    => $this->updated_at->toDateTimeString(),

            // Useful links
            'links' => [
                'self' => route('packages.show', $this->id),
                'book' => route('bookings.store', ['package_id' => $this->id]),
            ],
        ];
    }
}
