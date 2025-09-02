<?php

namespace App\Http\Resources;

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
            'id'          => $this->id,
            'slug'        => $this->slug, // SEO-friendly unique identifier
            'title'       => $this->title,
            'short_title' => str($this->title)->limit(40),

            // Rich descriptions
            'description' => $this->description,
            'highlights'  => $this->highlights ?? [], // quick bullet points
            'category'    => $this->category?->name,
            'tags'        => $this->tags->pluck('name'),

            // Pricing & booking info
            'price'       => [
                'base'       => $this->price,
                'currency'   => $this->currency ?? 'USD',
                'formatted'  => money_format('%.2n', $this->price),
                'discount'   => $this->when($this->discount, $this->discount),
                'is_on_sale' => (bool) $this->discount,
            ],
            'availability' => [
                'next_available_date' => $this->nextAvailableDate(),
                'spots_left'          => $this->spots_left,
                'duration'            => $this->duration_text, // e.g. "3h 30m"
                'is_sold_out'         => $this->isSoldOut(),
            ],

            // Media
            'cover_image' => $this->getFirstMediaUrl('cover'),
            'gallery'     => $this->getMedia('gallery')->map(fn($m) => $m->getUrl()),

            // Location details
            'location' => [
                'city'      => $this->city,
                'country'   => $this->country,
                'latitude'  => $this->latitude,
                'longitude' => $this->longitude,
                'map_url'   => "https://maps.google.com/?q={$this->latitude},{$this->longitude}",
            ],

            // Reviews & ratings
            'rating' => [
                'average'   => round($this->reviews_avg_rating, 1),
                'count'     => $this->reviews_count,
                'breakdown' => $this->reviews_breakdown, // e.g. {5: 120, 4: 32, 3: 10, ...}
            ],

            // Personalized / smart recommendations
            'personalization' => [
                'is_recommended'   => $this->when($request->user(), fn() => $this->isRecommendedFor($request->user())),
                'match_score'      => $this->when($request->user(), fn() => $this->recommendationScoreFor($request->user())),
                'similar_activities' => ActivityResource::collection($this->whenLoaded('similarActivities')),
            ],

            // System metadata
            'status'    => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // API Links (HATEOAS style for next-gen API UX)
            'links' => [
                'self'       => route('activities.show', $this->id),
                'book'       => route('bookings.store', ['activity_id' => $this->id]),
                'reviews'    => route('activities.reviews.index', $this->id),
                'similar'    => route('activities.similar', $this->id),
            ],
        ];
    }
}
