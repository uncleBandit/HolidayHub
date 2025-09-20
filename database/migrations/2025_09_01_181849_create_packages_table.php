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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            // Core Info
            $table->string('name'); // e.g. "Mombasa Beach Getaway"
            $table->string('slug')->unique(); // SEO friendly URL
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();

            // Relations
            $table->foreignId('destination_id')->nullable()
                ->constrained()
                ->onDelete('set null');
            $table->foreignId('agent_id')
                ->constrained()
                ->onDelete('set null'); // Travel agency/tour operator

            // Pricing
            $table->decimal('base_price', 10, 2); // default price
            $table->decimal('discount_price', 10, 2)->nullable(); // optional offer price
            $table->enum('currency', ['USD', 'EUR', 'GBP', 'KES'])->default('USD');

            // Duration
            $table->integer('duration_days')->nullable(); // e.g. 7
            $table->integer('duration_nights')->nullable(); // e.g. 6

            // Package Features
            $table->json('inclusions')->nullable(); // meals, transfers, tours, etc.
            $table->json('exclusions')->nullable(); // what’s not included
            $table->json('itinerary')->nullable(); // structured daily breakdown

            // Media
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();

            // Ratings & Popularity
            $table->decimal('avg_rating', 3, 2)->default(0.0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views')->default(0);

            // Availability
            $table->boolean('active')->default(true);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();

            // Metadata
            $table->json('meta_data')->nullable(); // SEO, tags, custom filters

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
