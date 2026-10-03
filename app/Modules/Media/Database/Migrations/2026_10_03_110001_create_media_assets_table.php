<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_post_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('disk', 50);
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['media_post_id', 'type'], 'media_assets_post_type_index');
            $table->index('status', 'media_assets_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
