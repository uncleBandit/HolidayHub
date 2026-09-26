<?php

namespace App\Modules\Activities\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            // Core identifiers
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->name, // DB column is `name`
            'short_title' => str($this->name)->limit(40),

            // Rich descriptions
            'description' => $this->description,
            'highlights' => $this->highlights ?? [], // optional: add column (json) if needed
            'category' => $this->type, // DB column is `type`
            'tags' => collect($this->tags ?? []), // DB stores JSON

            // Pricing & booking info
            'price' => [
                'base' => $this->base_price,
                'currency' => $this->currency ?? 'USD',
                'formatted' => number_format($this->base_price, 2).' '.$this->currency,
                'discount' => $this->when($this->discount, $this->discount), // only if you add `discount` col
                'is_on_sale' => (bool) $this->discount,
            ],
            'availability' => [
                'next_available_date' => $this->available_from,
                'available_until' => $this->available_to,
                'spots_left' => $this->capacity ? max(0, $this->capacity - $this->bookings_count) : null,
                'duration' => $this->duration_minutes
                    ? sprintf('%dh %02dm', intdiv($this->duration_minutes, 60), $this->duration_minutes % 60)
                    : null,
                'is_sold_out' => $this->capacity !== null && $this->capacity <= $this->bookings_count,
            ],

            // Media
            'cover_image' => $this->thumbnail,
            'gallery' => collect($this->gallery ?? []),
            'video_url' => $this->video_url,

            // Location details (via destination relation or columns if added later)
            'location' => [
                'destination_id' => $this->destination_id,
                'map_url' => $this->destination?->latitude && $this->destination?->longitude
                    ? "https://maps.google.com/?q={$this->destination->latitude},{$this->destination->longitude}"
                    : null,
            ],

            // Reviews & ratings
            'rating' => [
                'average' => round($this->rating, 1),
                'count' => $this->reviews_count,
                'breakdown' => $this->reviews_breakdown ?? null, // add if you implement breakdown
            ],

            // System metadata
            'status' => $this->is_active ? 'active' : 'inactive',
            'is_featured' => (bool) $this->is_featured,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // API Links (HATEOAS style for next-gen API UX)
            'links' => [
                'self' => route('activities.show', $this->id),
                'book' => route('bookings.store', ['activity_id' => $this->id]),
                'reviews' => route('activities.reviews.index', $this->id),
                'similar' => route('activities.similar', $this->id),
            ],
        ];
    }
}
