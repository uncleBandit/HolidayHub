<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants are the parties who supply services on the platform — accommodation
 * providers, B&B owners, travel agents.
 *
 * This table is the single source of truth for whether a tenant may trade.
 * `is_verified` previously existed independently on providers, agents and every
 * accommodation table, which meant no unified review queue and no way to record
 * who decided what, or why. A tenant row now owns that decision, and the
 * per-profile booleans are treated as denormalised copies.
 *
 * The tenantable morph points at the business profile (Provider or Agent) the
 * application belongs to. It is nullable so a tenant can apply before their
 * profile record exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // The login account that owns the business.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Which kind of service provider this is, denormalised from
            // tenantable_type so the review queue can filter without a join.
            $table->string('type', 32);

            // The business profile this application belongs to (Provider|Agent).
            $table->nullableMorphs('tenantable');

            $table->string('display_name');
            $table->string('contact_email');

            $table->string('status', 32)->default('pending');

            // Why the platform refused or withdrew access.
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();

            // Free-form notes visible only in the admin panel.
            $table->text('admin_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // The review queue's primary access path.
            $table->index(['status', 'created_at']);

            // One application per business profile. The unique index is on the
            // morph pair rather than on user_id so a user may legitimately
            // operate more than one business (e.g. a hotel and an agency).
            $table->unique(['tenantable_type', 'tenantable_id'], 'tenants_tenantable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
