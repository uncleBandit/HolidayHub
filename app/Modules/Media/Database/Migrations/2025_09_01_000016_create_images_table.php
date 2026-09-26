<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            // Polymorphic relationship (imageable_id & imageable_type)
            $table->morphs('imageable');

            // Core file info
            $table->string('path');                   // original file (local, S3, or CDN URL)
            $table->string('disk')->default('public'); // which storage disk it lives on
            $table->string('format', 10)->nullable();  // jpg, png, webp, avif, etc.

            // Metadata for accessibility & SEO
            $table->string('alt_text')->nullable();   // for screen readers & SEO
            $table->string('title')->nullable();      // hover/title attribute
            $table->string('caption')->nullable();    // human-readable description

            // Variants / optimization
            $table->json('variants')->nullable();     // e.g. { "thumb": "...", "medium": "...", "webp": "..." }

            // Gallery helpers
            $table->unsignedInteger('order')->default(0); // sort order
            $table->boolean('is_primary')->default(false); // main/cover image

            $table->timestamps();

            // Indexes for performance
            // NOTE: morphs('imageable') already emits an index on
            // (imageable_type, imageable_id) — redeclaring it fails on
            // PostgreSQL with "relation already exists".
            $table->index(['is_primary', 'order'], 'images_primary_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
