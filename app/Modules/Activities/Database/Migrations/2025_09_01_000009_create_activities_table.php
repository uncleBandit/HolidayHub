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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('hotel_id')->nullable()->constrained()->cascadeOnDelete();
            // Some activities can be tied to a hotel (spa, tour, etc.), or just a destination.

            // Core details
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->nullable(); // e.g., Adventure, Cultural, Relaxation
            $table->text('description')->nullable();

            // Media & SEO
            $table->string('thumbnail')->nullable();
            $table->json('gallery')->nullable(); // Store multiple images
            $table->string('video_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable(); // Flexible for search/filters

            // Activity Details
            $table->decimal('base_price', 10, 2)->nullable(); // Default price
            $table->enum('currency', ['USD', 'EUR', 'GBP', 'KES'])->default('USD');
            $table->integer('duration_minutes')->nullable(); // e.g., 90 mins
            $table->integer('capacity')->nullable(); // Max participants
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();

            // Availability
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();

            // Ratings & Popularity
            $table->decimal('rating', 3, 2)->default(0.0); // e.g., 4.75
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('bookings_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Relations
            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete(); // BnB belongs to a provider/host
            $table->foreignId('destination_id')
                ->constrained()
                ->cascadeOnDelete(); // Activity belongs to a destination/city

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
