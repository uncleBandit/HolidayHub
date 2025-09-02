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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Who wrote the review
            $table->foreignId('guest_id')
                ->constrained()
                ->cascadeOnDelete();

            // Polymorphic relation (hotel, destination, activity, testimonial, etc.)
            $table->morphs('reviewable'); // Creates reviewable_id & reviewable_type

            // Review details
            $table->unsignedTinyInteger('rating')->default(0); // 1–5 star ratings
            $table->string('title')->nullable(); // Optional review title
            $table->text('comment')->nullable();

            // Review type & moderation
            $table->string('type')->nullable();   // hotel, destination, activity, testimonial
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            // Flexible metadata (images, AI insights, tags, etc.)
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
