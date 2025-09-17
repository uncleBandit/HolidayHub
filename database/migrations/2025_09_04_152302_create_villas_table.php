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
        Schema::create('villas', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->foreignId('provider_id')
                  ->constrained()
                  ->cascadeOnDelete(); // Villa belongs to a provider
           // $table->foreignId('destination_id')
             //     ->constrained()
              //    ->cascadeOnDelete(); // Villa belongs to a destination

            // Core info
            $table->string('name');
            $table->string('slug')->unique(); // SEO-friendly URL
            $table->text('description')->nullable();

            // Location
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Features / characteristics
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->unsignedTinyInteger('bathrooms')->default(1);
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->boolean('has_private_pool')->default(false);
            $table->boolean('is_featured')->default(false); // highlight in homepage
            $table->json('amenities')->nullable(); // wifi, kitchen, BBQ, etc.
            $table->json('policies')->nullable(); // check-in/out, cancellation rules

            // Media
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable(); // multiple images

            // Pricing (base price; dynamic rates stored in a rates table)
            $table->decimal('avg_price_per_night', 10, 2)->nullable();

            // Ratings (denormalized for performance)
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['destination_id', 'provider_id']);
            $table->index(['city', 'country']);
            $table->json('meta_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villas');
    }
};
