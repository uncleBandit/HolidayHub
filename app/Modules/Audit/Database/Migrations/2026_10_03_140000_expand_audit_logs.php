<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->text('reason')->nullable();
            $table->string('request_id', 128)->nullable()->index();
            $table->string('correlation_id', 128)->nullable()->index();
            $table->index(['admin_id', 'created_at'], 'audit_logs_actor_created_index');
            $table->index(['created_at', 'action'], 'audit_logs_created_action_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_actor_created_index');
            $table->dropIndex('audit_logs_created_action_index');
            $table->dropIndex(['request_id']);
            $table->dropIndex(['correlation_id']);
            $table->dropColumn(['before', 'after', 'reason', 'request_id', 'correlation_id']);
        });
    }
};
