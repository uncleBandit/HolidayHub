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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')
                ->constrained('hotels')
                ->cascadeOnDelete(); // If hotel is deleted, delete its rooms

            $table->string('name'); // "Deluxe Suite", "Standard Room"
            $table->text('description')->nullable();
            $table->integer('capacity')->default(2); // max guests
            $table->integer('beds')->default(1); // number of beds
            $table->enum('bed_type', ['single', 'double', 'queen', 'king', 'bunk'])->default('double');
            $table->boolean('is_available')->default(true);

            // Features / amenities
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_wifi')->default(true);
            $table->boolean('has_tv')->default(false);
            $table->boolean('has_balcony')->default(false);

            // Media
            $table->string('thumbnail')->nullable(); // cover image
            $table->json('gallery')->nullable(); // multiple images
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
