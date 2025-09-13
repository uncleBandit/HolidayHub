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
        // Core amenities table
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();            // e.g. "Free WiFi"
            $table->string('slug')->unique();           // SEO friendly URL
            $table->string('icon')->nullable();         // optional icon for frontend
            $table->text('description')->nullable();    // optional details
            $table->enum('type', ['general', 'hotel', 'room', 'package', 'villa'])->default('hotel'); // flexibility for types
            $table->boolean('active')->default(true);   // enable/disable without deleting
            $table->timestamps();
        });

        // Polymorphic pivot table for amenities
        Schema::create('amenables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amenity_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->morphs('amenable'); // supports hotel, package, room, villa
            $table->timestamps();

            $table->unique(['amenity_id', 'amenable_type', 'amenable_id'], 'amenable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenables');
        Schema::dropIfExists('amenities');
    }
};
