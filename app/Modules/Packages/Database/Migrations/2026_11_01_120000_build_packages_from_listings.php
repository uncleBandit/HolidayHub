<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Packages become journeys built from real listings.
 *
 * An agent assembles a package day by day from published stays (a hotel room
 * type, a villa or a B&B for some nights) and experiences, plus simple extras
 * such as transfers. The guest price is the live price of those listings plus
 * the agent's margin. Booking a package creates one package booking for the
 * guest and a normal booking per listing for each provider, linked to it, with
 * the agent's commission recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('active');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('submitted_at');
            $table->text('rejection_reason')->nullable()->after('published_at');
            $table->unsignedTinyInteger('setup_step')->nullable()->after('rejection_reason');
            $table->string('cover_video')->nullable()->after('cover_image');
            // The agent's margin over the live price of the listings inside.
            $table->string('markup_type', 10)->default('percent')->after('discount_price');
            $table->decimal('markup_value', 10, 2)->default(0)->after('markup_type');
            // The code reads these; the table never had them, so booking crashed.
            $table->unsignedSmallInteger('min_guests')->default(1)->after('duration_nights');
            $table->unsignedSmallInteger('max_guests')->default(2)->after('min_guests');
        });

        Schema::create('package_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day')->default(1);
            $table->unsignedSmallInteger('sequence')->default(0);
            // stay | experience | transfer | free | note
            $table->string('kind', 20);
            // The listing it books (room_type, villa, bed_and_breakfast, activity); null for extras.
            $table->nullableMorphs('componentable');
            $table->foreignId('activity_option_id')->nullable();
            $table->unsignedSmallInteger('nights')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('title', 160)->nullable();
            $table->text('description')->nullable();
            // Extras (e.g. a transfer) carry their own price; listings are priced live.
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamps();

            $table->index(['package_id', 'day', 'sequence']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('package_booking_id')->nullable()->after('activity_session_id')->constrained('bookings')->nullOnDelete();
            $table->decimal('agent_commission', 12, 2)->nullable()->after('total_amount');
        });

        DB::table('roles')->updateOrInsert(
            ['name' => 'agent', 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_booking_id');
            $table->dropColumn('agent_commission');
        });

        Schema::dropIfExists('package_components');

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'submitted_at', 'published_at', 'rejection_reason', 'setup_step', 'cover_video',
                'markup_type', 'markup_value', 'min_guests', 'max_guests',
            ]);
        });
    }
};
