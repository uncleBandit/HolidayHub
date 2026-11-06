<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The "reel walk": a host-ordered sequence of scenes (a film or photo of one
 * space, with a title and caption) that guests play through like reels, from
 * the arrival to the view from the terrace. Shared by villas, B&Bs and hotels.
 *
 * Villas and B&Bs also gain what hotels already have: a cover film, the setup
 * step reached (so creation resumes after a refresh) and a pricing currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_scenes', function (Blueprint $table) {
            $table->id();
            $table->morphs('sceneable');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('space', 40)->nullable();
            $table->string('title', 120)->nullable();
            $table->text('caption')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_type', 10)->nullable(); // video | photo
            $table->timestamps();

            $table->index(['sceneable_type', 'sceneable_id', 'position']);
        });

        Schema::table('villas', function (Blueprint $table) {
            $table->string('cover_video')->nullable()->after('cover_image');
            $table->unsignedTinyInteger('setup_step')->nullable()->after('is_verified');
            $table->string('currency', 3)->default('KES')->after('avg_price_per_night');
            $table->unsignedInteger('size_sqm')->nullable()->after('bathrooms');
            $table->unsignedTinyInteger('min_nights')->default(1)->after('max_guests');
        });

        Schema::table('bed_and_breakfasts', function (Blueprint $table) {
            $table->string('cover_video')->nullable()->after('cover_image');
            $table->unsignedTinyInteger('setup_step')->nullable()->after('is_verified');
            $table->string('currency', 3)->default('KES')->after('price_per_night');
            $table->text('breakfast_details')->nullable()->after('has_breakfast');
        });
    }

    public function down(): void
    {
        Schema::table('bed_and_breakfasts', function (Blueprint $table) {
            $table->dropColumn(['cover_video', 'setup_step', 'currency', 'breakfast_details']);
        });

        Schema::table('villas', function (Blueprint $table) {
            $table->dropColumn(['cover_video', 'setup_step', 'currency', 'size_sqm', 'min_nights']);
        });

        Schema::dropIfExists('property_scenes');
    }
};
