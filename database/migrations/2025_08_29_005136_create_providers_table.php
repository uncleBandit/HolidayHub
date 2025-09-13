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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            // Core Identity
            $table->string('company_name'); // Provider brand
            $table->string('contact_person')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->text('bio')->nullable()->after('phone');

            // Business Details
            $table->string('business_license')->nullable();
            $table->string('tax_number')->nullable();
            $table->enum('provider_type', ['hotel', 'bnb', 'resort', 'apartment', 'hostel', 'villa', 'camp', 'other'])
                  ->default('hotel');

            // Location
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Profile & Verification
            $table->string('logo')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Make user_id required and cascade on delete
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

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
        Schema::dropIfExists('providers');
    }
};
