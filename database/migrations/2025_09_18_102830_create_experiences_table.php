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
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            // Core details
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->string('category')->nullable(); // safari, adventure, cultural, etc.
            $table->string('duration')->nullable(); // e.g. "3 hours", "1 day"

            // Pricing & capacity
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('capacity')->nullable();       // max slots per booking
            $table->unsignedInteger('included_guests')->default(1); // included in base price
            $table->unsignedInteger('max_guests')->nullable();      // upper limit

            // Media
            $table->string('cover_image')->nullable();
            $table->boolean('featured')->default(false);

            // Ownership (if hosted by providers)
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
