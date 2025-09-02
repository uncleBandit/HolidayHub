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
        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            // Pricing model
            $table->decimal('base_price', 10, 2); // default/night
            $table->decimal('discount_price', 10, 2)->nullable(); // seasonal or special deal

            // Dynamic pricing by date range
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Additional flexibility (JSON for advanced pricing rules)
            $table->json('meta')->nullable(); // {"weekend_rate":120, "holiday_rate":150}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_prices');
    }
};
