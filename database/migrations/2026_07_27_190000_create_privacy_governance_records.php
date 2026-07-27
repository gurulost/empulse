<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_erased_at')->nullable()->index();
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->string('privacy_policy_version', 64)->nullable()->index();
            $table->timestamp('privacy_acknowledged_at')->nullable();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('privacy_policy_version', 64)->nullable()->index();
        });

        Schema::create('privacy_acknowledgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('survey_assignment_id')->constrained('survey_assignments')->restrictOnDelete();
            $table->string('policy_version', 64);
            $table->char('policy_hash', 64);
            $table->timestamp('acknowledged_at');
            $table->string('source', 32)->default('survey');
            $table->timestamps();
            $table->unique(
                ['survey_assignment_id', 'policy_version'],
                'privacy_ack_assignment_policy_unique'
            );
        });

        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('subject_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['access', 'correction', 'erasure']);
            $table->enum('status', ['requested', 'identity_verified', 'approved', 'rejected', 'completed', 'blocked']);
            $table->text('reason')->nullable();
            $table->json('requested_changes')->nullable();
            $table->json('result_summary')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'type']);
        });

        Schema::create('legal_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope', 64)->default('all');
            $table->text('reason');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'subject_user_id', 'released_at']);
        });

        Schema::create('retention_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('dry_run')->default(true);
            $table->enum('status', ['planned', 'completed', 'failed']);
            $table->char('plan_hash', 64);
            $table->json('plan');
            $table->json('result')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_runs');
        Schema::dropIfExists('legal_holds');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('privacy_acknowledgments');

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn('privacy_policy_version');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropColumn(['privacy_policy_version', 'privacy_acknowledged_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_erased_at');
        });
    }
};
