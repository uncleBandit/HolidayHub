<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('activity_session_id')
                ->nullable()
                ->constrained('activity_sessions')
                ->restrictOnDelete();
            $table->index(['activity_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['activity_session_id']);
            $table->dropIndex(['activity_session_id', 'status']);
            $table->dropColumn('activity_session_id');
        });
    }
};
