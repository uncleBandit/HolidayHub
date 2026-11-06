<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a guest compares when choosing between rooms: size, view, how the beds
 * are made up, who can stay, and what the rate includes. Capacity stays as the
 * total sleeping count used by booking and pricing; adults and children split
 * it for display and validation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->unsignedSmallInteger('size_sqm')->nullable()->after('beds');
            $table->string('view', 40)->nullable()->after('size_sqm');
            $table->json('bed_configuration')->nullable()->after('view');
            $table->unsignedTinyInteger('max_adults')->default(2)->after('bed_configuration');
            $table->unsignedTinyInteger('max_children')->default(0)->after('max_adults');
            $table->boolean('breakfast_included')->default(false)->after('max_children');
            $table->boolean('refundable')->default(true)->after('breakfast_included');
            $table->boolean('smoking_allowed')->default(false)->after('refundable');
            $table->boolean('accessible')->default(false)->after('smoking_allowed');
        });

        // Existing room types sleep `capacity` people; treat them all as adults.
        DB::table('room_types')->update(['max_adults' => DB::raw('GREATEST(1, LEAST(capacity, 255))')]);
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn([
                'size_sqm',
                'view',
                'bed_configuration',
                'max_adults',
                'max_children',
                'breakfast_included',
                'refundable',
                'smoking_allowed',
                'accessible',
            ]);
        });
    }
};
