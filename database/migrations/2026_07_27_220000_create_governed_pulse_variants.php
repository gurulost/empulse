<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pulse_variant_versions', function (Blueprint $table) {
            $table->id();
            $table->string('variant_key', 160);
            $table->string('version', 64);
            $table->string('title');
            $table->string('purpose', 64);
            $table->foreignId('metric_registry_version_id')->constrained('metric_registry_versions')->restrictOnDelete();
            $table->string('metric_id', 160);
            $table->json('question_ids');
            $table->unsignedSmallInteger('estimated_minutes');
            $table->unsignedSmallInteger('minimum_days_between_invites')->default(30);
            $table->unsignedSmallInteger('maximum_pulses_per_90_days')->default(3);
            $table->text('claims_limit');
            $table->char('definition_hash', 64)->unique();
            $table->enum('status', ['draft', 'published', 'retired'])->default('published');
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at');
            $table->timestamps();
            $table->unique(['variant_key', 'version']);
        });

        Schema::table('survey_waves', function (Blueprint $table) {
            $table->foreignId('pulse_variant_version_id')
                ->nullable()
                ->constrained('pulse_variant_versions')
                ->restrictOnDelete();
            $table->foreignId('action_measurement_plan_id')
                ->nullable()
                ->constrained('action_measurement_plans')
                ->restrictOnDelete();
            $table->string('measurement_purpose', 64)->default('baseline');
            $table->unsignedSmallInteger('reminder_limit')->default(2);
        });

        Schema::create('survey_wave_audience_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_wave_cycle_id')->constrained('survey_wave_cycles')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 100);
            $table->json('policy_snapshot');
            $table->timestamps();
            $table->unique(['survey_wave_cycle_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_wave_audience_exclusions');
        Schema::table('survey_waves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pulse_variant_version_id');
            $table->dropConstrainedForeignId('action_measurement_plan_id');
            $table->dropColumn(['measurement_purpose', 'reminder_limit']);
        });
        Schema::dropIfExists('pulse_variant_versions');
    }
};
