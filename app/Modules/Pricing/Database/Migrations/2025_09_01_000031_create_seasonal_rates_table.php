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
        Schema::create('seasonal_rates', function (Blueprint $table) {
            $table->id();

            // Core pricing fields
            $table->decimal('rate', 10, 2);
            $table->string('currency', 3)->default('USD'); // ISO 4217 code

            // Validity window
            $table->date('start_date');
            $table->date('end_date');
            $table->morphs('seasonal_rateable');

            // Optional metadata
            $table->string('description')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();

            // Indexes for faster lookups
            // NOTE: the explicit name is required. The auto-generated name would
            // be 84 characters, and PostgreSQL silently truncates identifiers at
            // 63 — which invites collisions between similarly named indexes.
            $table->index(
                ['seasonal_rateable_type', 'seasonal_rateable_id', 'start_date', 'end_date'],
                'seasonal_rates_rateable_dates_index'
            );
            $table->index(['active'], 'seasonal_rates_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasonal_rates');
    }
};
