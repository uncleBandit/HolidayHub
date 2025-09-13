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
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            // Core details
            $table->string('name');
            $table->text('description')->nullable();

            // Relations
            $table->foreignId('destination_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('provider_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete(); // provider can be optional

            // Polymorphic relation: links to hotels, bnbs, villas, etc.
            $table->string('accommodation_type'); // e.g. Hotel, BnB, Villa
            $table->unsignedBigInteger('accommodation_id');

            // Common attributes for faster filtering
            $table->boolean('is_featured')->default(false);
            $table->decimal('avg_price_per_night', 10, 2)->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['accommodation_type', 'accommodation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
