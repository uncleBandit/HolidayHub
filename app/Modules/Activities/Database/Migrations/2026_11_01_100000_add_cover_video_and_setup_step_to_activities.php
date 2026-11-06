<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Experiences lead with film like stays do: an optional cover film, and the
 * setup step a host reached so the experience wizard can resume after a
 * refresh (null once submitted). Their reel walk lives in property_scenes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('cover_video')->nullable()->after('thumbnail');
            $table->unsignedTinyInteger('setup_step')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['cover_video', 'setup_step']);
        });
    }
};
