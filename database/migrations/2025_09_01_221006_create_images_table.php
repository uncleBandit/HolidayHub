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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            // Polymorphic relationship (imageable)
            $table->morphs('imageable'); // creates imageable_id & imageable_type

            // Core image details
            $table->string('path');        // storage path or cloud URL
            $table->string('alt_text')->nullable(); // accessibility & SEO
            $table->string('caption')->nullable();  // optional caption/description

            // Gallery helpers
            $table->integer('order')->default(0);   // sort order for galleries
            $table->boolean('is_primary')->default(false); // flag for main image

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
