<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency key for booking creation.
 *
 * BookingManager::create() arrives with (or generates) an idempotency key and
 * deduplicates against it, but bookings had no column to store it in, so every
 * create() attempt threw a QueryException on the lookup. This has never worked:
 * the write path was built against a schema the bookings table never had.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Unique in Postgres: NULLs are distinct, so rows created before
            // this migration (without a key) coexist with the constraint.
            $table->string('idempotency_key', 36)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
