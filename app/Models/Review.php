<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     * Keeps the API clean & safe for modern usage (Livewire/REST/GraphQL).
     */
    protected $fillable = [
        'guest_id',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'title',
        'comment',
        'type',        // Optional categorization: hotel, destination, activity, testimonial
        'status',      // e.g. pending, approved, rejected (for moderation/AI sentiment analysis)
        'meta',        // JSON field for extra info (e.g., images, tags, AI insights)
    ];

    /**
     * Attribute casting for API-friendly usage.
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Relationships
     */

    // Review belongs to a guest
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class,'user_id');
    }

    // Review can belong to Hotel, Destination, Activity, etc.
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helpers & Accessors
     */

    // Star rating accessor (convert rating -> stars)
    public function getStarsAttribute(): string
    {
        return str_repeat('⭐', (int) $this->rating);
    }

    // Check if review is approved
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    // Truncate long comments for previews
    public function getShortCommentAttribute(): string
    {
        return strlen($this->comment) > 120
            ? substr($this->comment, 0, 120) . '...'
            : $this->comment;
    }


}
