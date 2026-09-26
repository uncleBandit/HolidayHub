<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs the audit_logs table, which was created as a bare id + timestamps
 * stub while AuditLog and its callers were written against a full schema.
 *
 * Any admin action that called AuditLog::create([...]) with an action and
 * subject would have failed with a missing-column SQL error, so in practice no
 * platform-wide audit trail was ever written. This adds the columns the model
 * and callers expect.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('id')->constrained('users')->nullOnDelete();

            // Dot-separated verb, e.g. "tenant.approved" or "user.suspended".
            $table->string('action', 128)->nullable()->after('admin_id');

            $table->string('subject_type')->nullable()->after('action');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');

            $table->jsonb('meta')->nullable()->after('subject_id');

            $table->string('ip_address', 45)->nullable()->after('meta');
            $table->text('user_agent')->nullable()->after('ip_address');

            $table->index(['subject_type', 'subject_id'], 'audit_logs_subject_index');
            $table->index('action', 'audit_logs_action_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_subject_index');
            $table->dropIndex('audit_logs_action_index');
            $table->dropConstrainedForeignId('admin_id');
            $table->dropColumn(['action', 'subject_type', 'subject_id', 'meta', 'ip_address', 'user_agent']);
        });
    }
};
