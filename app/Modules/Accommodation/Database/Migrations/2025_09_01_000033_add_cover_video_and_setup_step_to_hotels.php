<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hotels lead with film: an optional cover video plays where the cover photo
 * used to sit (the photo stays as its poster and for cards).
 *
 * setup_step remembers how far a host got through setting a hotel up, so the
 * wizard can resume there after a refresh. Null once the hotel is submitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('cover_video')->nullable()->after('cover_image');
            $table->unsignedTinyInteger('setup_step')->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['cover_video', 'setup_step']);
        });
    }
};
