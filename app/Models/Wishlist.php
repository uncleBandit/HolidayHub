<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * You can expand this later to include metadata (e.g., tags, notes, etc.)
     */
    protected $fillable = [
        'user_id',
        'wishlistable_id',
        'wishlistable_type',
        'notes',            // optional personal note
        'priority',         // low, medium, high (for ranking trips)
    ];

    /**
     * A wishlist always belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The item being wishlisted (Hotel, Villa, BnB, Tour, Flight, etc.)
     */
    public function wishlistable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for retrieving wishlist items by type.
     */
    public function scopeOfType($query, string $modelClass)
    {
        return $query->where('wishlistable_type', $modelClass);
    }

    /**
     * Scope for retrieving wishlist items by priority.
     */
    public function scopePriority($query, string $level)
    {
        return $query->where('priority', $level);
    }
}
