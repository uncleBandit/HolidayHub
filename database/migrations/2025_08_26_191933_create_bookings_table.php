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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // User who made the booking
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Hotel + Room reference
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->cascadeOnDelete();

            // Booking details
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedInteger('guests_adults')->default(1);
            $table->unsignedInteger('guests_children')->default(0);

            // Pricing details
            $table->decimal('price_per_night', 10, 2)->nullable(); // snapshot at booking time
            $table->decimal('total_amount', 10, 2)->nullable();   // final charged price
            $table->string('currency', 3)->default('USD');

            // Payment tracking
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])
                ->default('pending');
            $table->enum('payment_method', ['credit_card', 'paypal', 'mpesa', 'bank_transfer', 'other'])
                ->nullable();

            // Booking lifecycle
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])
                ->default('pending');

            // Metadata
            $table->json('special_requests')->nullable(); // e.g. "extra pillows", "vegan meals"
            $table->string('confirmation_code')->unique(); // human-readable booking ref
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
