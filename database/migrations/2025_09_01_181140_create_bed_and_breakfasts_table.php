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
        Schema::create('bed_and_breakfasts', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->foreignId('provider_id')
                  ->constrained()
                  ->cascadeOnDelete(); // BnB belongs to a provider/host
           // $table->foreignId('destination_id')
                //  ->constrained()
                //  ->cascadeOnDelete(); // BnB belongs to a destination/city

            // Core info
            $table->string('name');
            $table->string('slug')->unique(); // SEO-friendly URL
            $table->text('description')->nullable();
            $table->integer('rooms')->default(0);
            $table->boolean('has_breakfast')->default(false);

            // Location
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Features
            $table->boolean('is_featured')->default(false); // highlight in homepage
            $table->json('amenities')->nullable(); // e.g. free breakfast, wifi, parking
            $table->json('policies')->nullable(); // check-in/out, cancellation rules

            // Media
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();

            // Pricing
            $table->decimal('price_per_night', 10, 2)->nullable();
            $table->unsignedInteger('max_guests')->default(2);
            $table->json('seasonal_pricing')->nullable(); // e.g. summer rates

            // Ratings
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);

            $table->timestamps();
            $table->softDeletes();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bead_and_breakfasts');
    }
};
