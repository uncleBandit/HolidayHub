<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'path',          // storage path or URL
        'alt_text',      // accessibility / SEO
        'caption',       // optional caption/description
        'imageable_id',  // for polymorphic relation
        'imageable_type',
        'order',         // to control gallery order
        'is_primary',    // flag for main image
    ];

    /**
     * Casts for attributes.
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Polymorphic relationship - image can belong to any model.
     * Example: Hotel, Room, Destination, User profile, etc.
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Accessor for full image URL.
     * Works with local disk or cloud storage (e.g., S3, GCP).
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Scope for primary images only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order images in galleries.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
