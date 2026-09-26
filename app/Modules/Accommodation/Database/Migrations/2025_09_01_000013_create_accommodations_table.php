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
            // Relations
            $table->foreignId('destination_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete(); // A provider is required

            // Polymorphic relation: links to hotels, bnbs, villas, etc.
            $table->morphs('bookable');

            // Common attributes for faster filtering and display
            $table->boolean('is_featured')->default(false);
            $table->decimal('avg_price_per_night', 10, 2)->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance on polymorphic relations
            //  $table->index(['bookable_type', 'bookable_id']);
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
