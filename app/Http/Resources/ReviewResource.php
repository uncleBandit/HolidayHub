<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,

            // Reviewer info (privacy-conscious, no emails)
            'reviewer'    => [
                'id'       => $this->user->id,
                'name'     => $this->user->name,
                'avatar'   => $this->user->profile_photo_url ?? null,
                'is_verified_guest' => $this->guest? $this->guest->bookings()
                                            ->where('hotel_id', $this->hotel_id)
                                            ->where('status', 'confirmed')
                                            ->exists()
                                        : false,
                   
            ],

            // Review content
            'rating'      => (float) $this->rating,   // 1–5 scale
            'title'       => $this->title ?? null,
            'comment'     => $this->comment,
            'photos'      => $this->whenLoaded('media', function () {
                return $this->media->map(fn($m) => asset('storage/' . $m->path));
            }),

            // Engagement
            'helpful_votes' => $this->helpful_votes ?? 0,
            'reported'      => $this->reported ?? false,

            // Metadata
            'created_at'  => $this->created_at->toDateTimeString(),
            'updated_at'  => $this->updated_at->toDateTimeString(),

            // Context
            'hotel'       => [
                'id'   => $this->hotel->id,
                'name' => $this->hotel->name,
            ],
            'room'        => $this->whenLoaded('room', function () {
                return [
                    'id'   => $this->room->id,
                    'name' => $this->room->name,
                ];
            }),

            // Advanced analytics (for AI/next-gen UX)
            'sentiment'   => $this->sentiment ?? 'neutral', // future: AI-powered
            'tags'        => $this->tags ?? [], // e.g. ["clean rooms", "great food"]
            'language'    => $this->language ?? 'en',
            'translated_comment' => $this->when(
                $this->language !== app()->getLocale(),
                fn() => $this->translated_comment ?? null
            ),
        ];
    }
}
