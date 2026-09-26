<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only history of tenant verification decisions.
 *
 * `tenants.status` holds only the current state, so it cannot answer "who
 * approved this, when, and on what grounds?" — the question an admin has to
 * answer when a tenant disputes a decision. Every transition is appended here
 * instead of overwriting, which also makes the workflow auditable after the
 * fact.
 *
 * admin_id is nullable because not every event is an admin action: a tenant
 * submitting an application records a null admin_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Null for tenant-initiated events such as applying or resubmitting.
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);

            $table->text('reason')->nullable();

            // Free-form context: submitted documents, licence numbers checked, etc.
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_verifications');
    }
};
