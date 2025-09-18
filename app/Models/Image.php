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
        'path',          // storage path or cloud URL
        'disk',          // storage disk, e.g., public, s3
        'format',        // jpg, png, webp, avif, etc.
        'alt_text',      // accessibility / SEO
        'title',         // optional hover/title
        'caption',       // optional caption/description
        'variants',      // JSON with optimized sizes (thumb, medium, webp)
        'order',         // to control gallery order
        'is_primary',    // flag for main image
        'imageable_id',  // for polymorphic relation
        'imageable_type',
    ];

    /**
     * Casts for attributes.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'order'      => 'integer',
        'variants'   => 'array',  // decode JSON to array
    ];

    /**
     * Polymorphic relationship - image can belong to any model.
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Accessor for full image URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
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

    /**
     * Helper to get a variant image URL (thumb, medium, webp, etc.).
     */
    public function variant(string $type): ?string
    {
        return $this->variants[$type] ?? null;
    }
}
