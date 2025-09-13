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
            $table->index(['seasonal_rateable_type', 'seasonal_rateable_id', 'start_date', 'end_date']);
            $table->index(['active']);
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
