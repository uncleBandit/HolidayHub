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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            // Polymorphic relationship: works for rooms, hotels, villas, tours, etc.
            $table->morphs('bookable'); // creates bookable_id (unsignedBigInt) + bookable_type (string)

            // Availability period
            $table->date('start_date');
            $table->date('end_date');

            // Number of units available (e.g., 5 rooms, 2 villas, 10 tour slots)
            $table->unsignedInteger('quantity')->default(1);

            // Pricing (optional override of base price for this date range)
            $table->decimal('price_per_night', 10, 2)->nullable();

            // Status flag (available, blocked, maintenance, holiday blackout, etc.)
            $table->enum('status', ['available', 'unavailable', 'blocked', 'maintenance'])->default('available');

            $table->timestamps();

            // Prevent duplicate entries for the same bookable & date range
            $table->unique(['bookable_id', 'bookable_type', 'start_date', 'end_date'], 'uniq_availability_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
