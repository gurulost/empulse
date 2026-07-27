<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('active')->index();
            $table->timestamp('closed_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('google_id', 'users_google_id_unique');
            $table->unique('fb_id', 'users_fb_id_unique');
        });

        Schema::table('survey_waves', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
        });

        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('role');
            $table->string('status')->default('active');
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status', 'valid_to'], 'membership_current_idx');
            $table->index(['user_id', 'company_id', 'valid_to'], 'membership_user_history_idx');
        });

        Schema::create('organization_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->uuid('stable_key')->unique();
            $table->string('type')->default('department');
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type', 'status'], 'org_unit_lookup_idx');
        });

        Schema::create('organization_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained('organization_memberships')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('reports_to_membership_id')->nullable()->constrained('organization_memberships')->restrictOnDelete();
            $table->string('status')->default('active');
            $table->string('unresolved_reason')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['membership_id', 'valid_to'], 'org_assignment_current_idx');
            $table->index(['organization_unit_id', 'valid_to'], 'org_assignment_unit_idx');
        });

        Schema::create('survey_wave_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_wave_id')->constrained('survey_waves')->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('status')->default('preparing');
            $table->string('instrument_hash', 64);
            $table->string('metric_definition_hash', 64);
            $table->string('audience_hash', 64)->nullable();
            $table->unsignedInteger('audience_count')->default(0);
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->unique(['survey_wave_id', 'sequence']);
            $table->index(['survey_wave_id', 'status'], 'wave_cycle_status_idx');
        });

        Schema::create('survey_wave_audience_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_wave_cycle_id')->constrained('survey_wave_cycles')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('organization_membership_id')->nullable()->constrained('organization_memberships')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->unsignedSmallInteger('role');
            $table->json('snapshot');
            $table->string('inclusion_reason')->default('target_role');
            $table->timestamps();

            $table->unique(['survey_wave_cycle_id', 'user_id'], 'cycle_audience_user_unique');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->foreignId('survey_wave_cycle_id')->nullable()->after('survey_wave_id')
                ->constrained('survey_wave_cycles')->restrictOnDelete();
            $table->foreignId('survey_wave_audience_member_id')->nullable()->after('survey_wave_cycle_id')
                ->constrained('survey_wave_audience_members')->restrictOnDelete();
            $table->json('cohort_snapshot')->nullable();
            $table->unique(
                ['survey_wave_cycle_id', 'user_id'],
                'assignment_cycle_user_unique'
            );
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignId('survey_wave_cycle_id')->nullable()->after('survey_wave_id')
                ->constrained('survey_wave_cycles')->restrictOnDelete();
            $table->foreignId('survey_wave_audience_member_id')->nullable()->after('survey_wave_cycle_id')
                ->constrained('survey_wave_audience_members')->restrictOnDelete();
            $table->json('cohort_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('survey_wave_audience_member_id');
            $table->dropConstrainedForeignId('survey_wave_cycle_id');
            $table->dropColumn('cohort_snapshot');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropUnique('assignment_cycle_user_unique');
            $table->dropConstrainedForeignId('survey_wave_audience_member_id');
            $table->dropConstrainedForeignId('survey_wave_cycle_id');
            $table->dropColumn('cohort_snapshot');
        });

        Schema::dropIfExists('survey_wave_audience_members');
        Schema::dropIfExists('survey_wave_cycles');
        Schema::dropIfExists('organization_assignments');
        Schema::dropIfExists('organization_units');
        Schema::dropIfExists('organization_memberships');

        Schema::table('survey_waves', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['status', 'closed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_google_id_unique');
            $table->dropUnique('users_fb_id_unique');
        });
    }
};
