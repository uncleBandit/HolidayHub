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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            // Core hotel info

            $table->string('name');
            $table->string('slug')->unique(); // SEO friendly URL
            $table->text('description')->nullable();

            // Location
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Hotel features
            $table->unsignedTinyInteger('stars')->default(3); // 1–5 stars
            $table->boolean('is_featured')->default(false); // highlight in homepage
            //$table->json('amenities')->nullable(); // e.g. wifi, spa, pool
            $table->json('policies')->nullable(); // check-in, check-out, cancellation rules

            // Media
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable(); // multiple images

            // Pricing (avg base price to show before dynamic room rates)
            $table->decimal('avg_price_per_night', 10, 2)->nullable();

            // Ratings (to optimize queries instead of calculating every time)
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Relations
            $table->foreignId('provider_id')
                  ->constrained()
                  ->cascadeOnDelete(); // Hotel belongs to a provider
            //$table->foreignId('destination_id')
                //  ->constrained()
                  //->cascadeOnDelete(); // Hotel belongs to a destination
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
