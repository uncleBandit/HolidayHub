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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            // Core Identity
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();

            // Business/Agency Details
            $table->string('agency_name')->nullable();
            $table->string('agency_license')->nullable(); // regulatory registration number
            $table->string('specialization')->nullable(); // e.g., Hotels, Tours, Flights, Cruises

            // Location
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();

            // Commission & Business Model
            $table->decimal('commission_rate', 5, 2)->default(0.00); // percentage
            $table->enum('business_type', ['individual', 'agency', 'corporate'])->default('individual');

            // Profile & Verification
            $table->string('profile_photo')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Relationship to User (optional, if they also log in as platform users)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Status
            $table->boolean('active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
