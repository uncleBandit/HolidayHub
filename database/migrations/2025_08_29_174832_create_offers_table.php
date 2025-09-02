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
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedTinyInteger('discount_percent')->default(0);

            // Optional relationships
            $table->foreignId('destination_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->nullable()->constrained()->cascadeOnDelete();

            // Dates
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // Featured flag
            $table->boolean('is_featured')->default(false);

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['is_featured', 'start_date', 'end_date']);
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
