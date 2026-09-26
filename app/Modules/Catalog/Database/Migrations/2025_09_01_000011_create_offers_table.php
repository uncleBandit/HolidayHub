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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            // Basic offer details
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('main_image')->nullable(); // primary image
            $table->json('gallery_images')->nullable(); // optional gallery
            $table->decimal('price', 12, 2)->default(0.00);
            $table->unsignedTinyInteger('discount_percent')->default(0);

            // polymorphic relation: hotel, villa, package, etc.
            $table->morphs('offerable');

            // Optional destination association
            $table->foreignId('destination_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->cascadeOnDelete();

            // Dates and duration
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // Flags & status
            $table->boolean('is_featured')->default(false);
            $table->boolean('active')->default(true); // active/inactive
            $table->unsignedSmallInteger('max_capacity')->nullable(); // optional capacity

            // Optional ratings & tags for filtering
            $table->decimal('rating', 3, 2)->nullable(); // e.g., 4.5
            $table->json('tags')->nullable();

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['is_featured', 'active', 'start_date', 'end_date']);
            $table->index(['price', 'discount_percent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
