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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            // Basic details
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Demographics
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'non-binary', 'prefer_not_to_say'])->nullable();
            $table->string('nationality')->nullable();

            // Identity & Verification
            $table->string('passport_number')->nullable()->unique();
            $table->string('id_number')->nullable()->unique();
            $table->boolean('verified')->default(false);

            // Preferences & Loyalty
            $table->string('preferred_language')->default('en');
            $table->string('preferred_currency')->default('USD');
            $table->string('loyalty_tier')->default('standard'); // standard, silver, gold, platinum
            $table->integer('loyalty_points')->default(0);

            // Contact & Emergency
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // Travel Information
            $table->string('frequent_flyer_number')->nullable();
            $table->string('special_requests')->nullable(); // e.g. vegetarian, wheelchair access

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // System fields
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
