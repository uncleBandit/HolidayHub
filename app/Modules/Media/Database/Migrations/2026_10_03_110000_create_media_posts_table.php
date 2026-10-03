<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('targetable');
            $table->string('type', 20);
            $table->string('title')->nullable();
            $table->string('caption', 1000)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('visibility', 20)->default('public');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('moderation_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility', 'published_at'], 'media_posts_public_feed_index');
            $table->index(['provider_id', 'status', 'published_at'], 'media_posts_provider_index');
            $table->index(['type', 'status', 'published_at'], 'media_posts_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_posts');
    }
};
