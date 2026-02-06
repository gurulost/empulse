<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->index(['user_id', 'submitted_at'], 'sr_user_submitted_idx');
            $table->index(['survey_wave_id', 'submitted_at'], 'sr_wave_submitted_idx');
            $table->index(['survey_version_id', 'submitted_at'], 'sr_version_submitted_idx');
            $table->index(['wave_label', 'submitted_at'], 'sr_label_submitted_idx');
            $table->index('submitted_at', 'sr_submitted_idx');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->index('wave_label', 'sa_wave_label_idx');
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            $table->index(['response_id', 'question_key'], 'sans_response_question_idx');
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropIndex('sans_response_question_idx');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropIndex('sa_wave_label_idx');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropIndex('sr_submitted_idx');
            $table->dropIndex('sr_label_submitted_idx');
            $table->dropIndex('sr_version_submitted_idx');
            $table->dropIndex('sr_wave_submitted_idx');
            $table->dropIndex('sr_user_submitted_idx');
        });
    }
};
