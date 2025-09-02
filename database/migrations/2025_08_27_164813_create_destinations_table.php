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
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();

            // Core destination details
            $table->string('name'); // e.g. "Mombasa Beach"
            $table->string('slug')->unique(); // SEO-friendly URL
            $table->string('country');
            $table->string('city')->nullable();

            // Rich descriptions & media
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('thumbnail')->nullable(); // small preview image
            $table->json('gallery')->nullable(); // multiple images (JSON array of URLs)
            $table->integer('average_cost')->nullable();
            $table->string('image_url')->nullable();
            $table->string('tagline')->nullable();

            // Location metadata
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Popularity & ranking
            $table->unsignedBigInteger('popularity_score')->default(0); // used for trending
            $table->boolean('is_featured')->default(false); // highlight destination

            // Travel info
            $table->string('best_season')->nullable(); // e.g. "June - August"
            $table->json('highlights')->nullable(); // e.g. ["beaches", "nightlife", "safari"]

            // SEO & meta
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
