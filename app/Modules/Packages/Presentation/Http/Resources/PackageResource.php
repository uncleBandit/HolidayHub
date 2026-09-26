<?php

namespace App\Modules\Packages\Presentation\Http\Resources;

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
        // Compute final price based on discount_price if present
        $finalPrice = $this->discount_price ?? $this->base_price;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'full_description' => $this->full_description,
            'description_preview' => Str::limit(strip_tags($this->full_description), 300),

            // Relations
            'destination_id' => $this->destination_id,
            'agent_id' => $this->agent_id,
            'provider_id' => $this->provider_id ?? null, // optional if you attach a provider

            // Pricing
            'base_price' => (float) $this->base_price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'final_price' => (float) $finalPrice,
            'currency' => $this->currency,

            // Duration
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,

            // Features
            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],
            'itinerary' => $this->itinerary ?? [],

            // Media
            'cover_image' => $this->cover_image ? asset('storage/'.$this->cover_image) : null,
            'gallery' => $this->gallery ?? [],

            // Ratings & popularity
            'avg_rating' => (float) $this->avg_rating,
            'reviews_count' => $this->reviews_count,
            'is_featured' => (bool) $this->is_featured,
            'views' => $this->views,

            // Availability
            'active' => (bool) $this->active,
            'available_from' => $this->available_from?->toDateString(),
            'available_to' => $this->available_to?->toDateString(),
            'is_available' => $this->available_from && $this->available_to
                                    ? now()->between($this->available_from, $this->available_to)
                                    : (bool) $this->active,

            // Metadata
            'meta_data' => $this->meta_data ?? [],

            // System timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),

            // API Links
            'links' => [
                'self' => route('packages.show', $this->id),
                'book' => route('bookings.store', ['package_id' => $this->id]),
            ],
        ];
    }
}
