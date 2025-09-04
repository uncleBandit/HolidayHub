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
        Schema::create('package_features', function (Blueprint $table) {
            $table->id();

            // Core details
            $table->string('title');
            $table->string('slug')->unique(); // SEO-friendly URLs
            $table->text('description')->nullable();

            // Destination / location
            $table->string('destination')->index(); // e.g., "Mombasa, Kenya"
            $table->string('country')->nullable()->index();

            // Pricing
            $table->decimal('price', 10, 2)->index(); // supports filtering/sorting
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount_price', 10, 2)->nullable(); // optional promo price

            // Duration & availability
            $table->unsignedInteger('duration_days')->default(1);
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();

            // Capacity
            $table->unsignedInteger('max_guests')->nullable();

            // Relations
            $table->foreignId('agent_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            // Media
            $table->string('image_url')->nullable(); // cover image

            // Status
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft')
                ->index();

            // Search/filter enhancements
            $table->json('tags')->nullable(); // e.g., ["beach", "family", "luxury"]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_features');
    }
};
