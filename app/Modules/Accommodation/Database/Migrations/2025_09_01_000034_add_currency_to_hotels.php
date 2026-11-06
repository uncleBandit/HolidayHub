<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The currency a hotel prices in. Its room types and seasonal rates follow it,
 * and guests see and book in it. Existing hotels were priced in shillings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('currency', 3)->default('KES')->after('avg_price_per_night');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
