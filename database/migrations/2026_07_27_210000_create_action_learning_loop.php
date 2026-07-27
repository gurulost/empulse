<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_findings', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('survey_wave_id')->constrained('survey_waves')->restrictOnDelete();
            $table->foreignId('survey_wave_cycle_id')->nullable()->constrained('survey_wave_cycles')->restrictOnDelete();
            $table->foreignId('metric_registry_version_id')->constrained('metric_registry_versions')->restrictOnDelete();
            $table->string('metric_id', 160);
            $table->string('cohort_key', 255)->default('company');
            $table->json('cohort_snapshot');
            $table->json('evidence_snapshot');
            $table->char('evidence_hash', 64);
            $table->text('interpretation');
            $table->text('limits');
            $table->enum('status', ['proposed', 'accepted', 'dismissed'])->default('proposed');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['company_id', 'survey_wave_id', 'metric_id', 'cohort_key', 'evidence_hash'],
                'diagnostic_findings_evidence_unique'
            );
            $table->index(['company_id', 'status', 'created_at']);
        });

        Schema::create('finding_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_finding_id')->constrained('diagnostic_findings')->restrictOnDelete();
            $table->enum('decision', ['accepted', 'dismissed', 'reopened']);
            $table->text('rationale');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('intervention_playbook_versions', function (Blueprint $table) {
            $table->id();
            $table->string('intervention_key', 100);
            $table->string('version', 64);
            $table->string('title');
            $table->text('description');
            $table->json('eligible_metric_patterns');
            $table->json('steps');
            $table->json('guardrails');
            $table->text('claims_limit');
            $table->enum('status', ['draft', 'published', 'retired'])->default('draft');
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['intervention_key', 'version']);
        });

        Schema::create('leadership_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('diagnostic_finding_id')->constrained('diagnostic_findings')->restrictOnDelete();
            $table->foreignId('intervention_playbook_version_id')->nullable()->constrained('intervention_playbook_versions')->restrictOnDelete();
            $table->string('title');
            $table->text('hypothesis');
            $table->text('planned_change');
            $table->json('success_criteria');
            $table->json('guardrails')->nullable();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft', 'committed', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('target_date')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'target_date']);
        });

        Schema::create('action_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leadership_action_id')->constrained('leadership_actions')->restrictOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('action_communications', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('leadership_action_id')->constrained('leadership_actions')->restrictOnDelete();
            $table->string('audience', 160);
            $table->string('channel', 64)->default('manager_shared');
            $table->text('message');
            $table->enum('status', ['draft', 'published', 'withdrawn'])->default('draft');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('action_measurement_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('leadership_action_id')->constrained('leadership_actions')->restrictOnDelete();
            $table->foreignId('baseline_wave_id')->constrained('survey_waves')->restrictOnDelete();
            $table->foreignId('followup_wave_id')->nullable()->constrained('survey_waves')->restrictOnDelete();
            $table->string('metric_id', 160);
            $table->char('baseline_instrument_hash', 64)->nullable();
            $table->char('baseline_metric_hash', 64);
            $table->string('target_direction', 32);
            $table->decimal('minimum_meaningful_change', 8, 3)->nullable();
            $table->json('audience_definition');
            $table->enum('status', ['planned', 'ready', 'evaluated', 'incompatible'])->default('planned');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('action_outcomes', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('action_measurement_plan_id')->constrained('action_measurement_plans')->restrictOnDelete();
            $table->foreignId('followup_wave_id')->constrained('survey_waves')->restrictOnDelete();
            $table->enum('result', ['movement_observed', 'no_meaningful_movement', 'declined', 'inconclusive', 'incompatible']);
            $table->json('evaluation_snapshot');
            $table->char('evaluation_hash', 64);
            $table->text('interpretation');
            $table->text('causality_limit');
            $table->foreignId('evaluated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('evaluated_at');
            $table->timestamps();
            $table->unique(['action_measurement_plan_id', 'followup_wave_id']);
        });

        Schema::create('advisor_work_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('diagnostic_finding_id')->nullable()->constrained('diagnostic_findings')->restrictOnDelete();
            $table->foreignId('leadership_action_id')->nullable()->constrained('leadership_actions')->restrictOnDelete();
            $table->string('kind', 64);
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'claimed', 'completed', 'dismissed'])->default('open');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority', 'due_at']);
        });

        Schema::create('action_loop_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('subject_type', 120);
            $table->string('subject_id', 120);
            $table->json('properties')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['company_id', 'name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_loop_events');
        Schema::dropIfExists('advisor_work_items');
        Schema::dropIfExists('action_outcomes');
        Schema::dropIfExists('action_measurement_plans');
        Schema::dropIfExists('action_communications');
        Schema::dropIfExists('action_status_events');
        Schema::dropIfExists('leadership_actions');
        Schema::dropIfExists('intervention_playbook_versions');
        Schema::dropIfExists('finding_decisions');
        Schema::dropIfExists('diagnostic_findings');
    }
};
