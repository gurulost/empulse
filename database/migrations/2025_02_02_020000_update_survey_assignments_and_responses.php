<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_assignments', 'survey_version_id')) {
                $table->unsignedBigInteger('survey_version_id')->nullable()->after('survey_id');
                $table->foreign('survey_version_id')
                    ->references('id')
                    ->on('survey_versions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('survey_assignments', 'draft_answers')) {
                $table->json('draft_answers')->nullable()->after('status');
            }

            if (!Schema::hasColumn('survey_assignments', 'last_autosaved_at')) {
                $table->timestamp('last_autosaved_at')->nullable()->after('draft_answers');
            }
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_responses', 'survey_version_id')) {
                $table->unsignedBigInteger('survey_version_id')->nullable()->after('survey_id');
                $table->foreign('survey_version_id')
                    ->references('id')
                    ->on('survey_versions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('survey_responses', 'duration_ms')) {
                $table->unsignedInteger('duration_ms')->nullable()->after('submitted_at');
            }
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('survey_answers', 'question_id')) {
                $table->unsignedBigInteger('question_id')->nullable()->after('response_id');
            }

            if (!Schema::hasColumn('survey_answers', 'survey_item_id')) {
                $table->unsignedBigInteger('survey_item_id')->nullable()->after('question_id');
                $table->foreign('survey_item_id')
                    ->references('id')
                    ->on('survey_items')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('survey_answers', 'metadata')) {
                $table->json('metadata')->nullable()->after('value_numeric');
            }
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            if (Schema::hasColumn('survey_answers', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('survey_answers', 'survey_item_id')) {
                $table->dropForeign(['survey_item_id']);
                $table->dropColumn('survey_item_id');
            }
            if (Schema::hasColumn('survey_answers', 'question_id')) {
                $table->dropColumn('question_id');
                $table->unsignedBigInteger('question_id');
                $table->foreign('question_id')->references('id')->on('survey_questions')->onDelete('cascade');
            }
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            if (Schema::hasColumn('survey_responses', 'duration_ms')) {
                $table->dropColumn('duration_ms');
            }
            if (Schema::hasColumn('survey_responses', 'survey_version_id')) {
                $table->dropForeign(['survey_version_id']);
                $table->dropColumn('survey_version_id');
            }
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('survey_assignments', 'last_autosaved_at')) {
                $table->dropColumn('last_autosaved_at');
            }
            if (Schema::hasColumn('survey_assignments', 'draft_answers')) {
                $table->dropColumn('draft_answers');
            }
            if (Schema::hasColumn('survey_assignments', 'survey_version_id')) {
                $table->dropForeign(['survey_version_id']);
                $table->dropColumn('survey_version_id');
            }
        });
    }
};
