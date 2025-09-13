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

            // Polymorphic relation: can belong to packages, hotels, villas, tours, etc.
            $table->unsignedBigInteger('featureable_id');
            $table->string('featureable_type');

            // Feature details
            $table->string('name');              // e.g. "Free Airport Pickup"
            $table->string('icon')->nullable(); // Optional icon reference
            $table->string('category')->nullable(); // e.g. Meals, Activities, Transport
            $table->text('description')->nullable();

            // Marketing/UX
            $table->boolean('is_highlighted')->default(false);

            // Flexible structured data
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['featureable_id', 'featureable_type']);
            $table->index(['category']);
            $table->index(['is_highlighted']);
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
