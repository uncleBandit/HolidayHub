<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        // Decode meta JSON if available
        $meta = $this->meta ? json_decode($this->meta, true) : [];

        return [
            'id' => $this->id,

            // Guest info (privacy-conscious)
            'guest' => [
                'id'   => $this->guest->id,
                'name' => $this->guest->first_name . ' ' . $this->guest->last_name,
                'avatar' => $this->guest->user->profile_photo_url ?? null,
                'is_verified_guest' => $this->guest?->bookings()
                                        ->where('bookable_id', $this->reviewable_id)
                                        ->where('bookable_type', $this->reviewable_type)
                                        ->where('status', 'confirmed')
                                        ->exists() ?? false,
            ],

            // Review content
            'rating'  => (float) $this->rating,
            'title'   => $this->title,
            'comment' => $this->comment,
            'photos'  => $meta['photos'] ?? [],

            // Moderation & engagement
            'status'         => $this->status,
            'helpful_votes'  => $meta['helpful_votes'] ?? 0,
            'reported'       => $meta['reported'] ?? false,

            // Polymorphic relation (reviewable entity)
            'reviewable' => [
                'type' => class_basename($this->reviewable_type),
                'id'   => $this->reviewable_id,
                'name' => $this->whenLoaded('reviewable', fn() => $this->reviewable->name ?? null),
            ],

            // Advanced / AI features
            'sentiment'           => $meta['sentiment'] ?? 'neutral',
            'tags'                => $meta['tags'] ?? [],
            'language'            => $meta['language'] ?? 'en',
            'translated_comment'  => $meta['translated_comment'] ?? null,

            // Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
