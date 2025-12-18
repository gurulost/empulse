<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('survey_answers') || !Schema::hasColumn('survey_answers', 'question_id')) {
            return;
        }

        try {
            Schema::table('survey_answers', function (Blueprint $table) {
                $table->dropForeign(['question_id']);
            });
        } catch (Throwable) {
            // Some environments may already have the constraint removed.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('survey_answers') || !Schema::hasColumn('survey_answers', 'question_id')) {
            return;
        }

        try {
            Schema::table('survey_answers', function (Blueprint $table) {
                $table->foreign('question_id')
                    ->references('id')
                    ->on('survey_questions')
                    ->onDelete('cascade');
            });
        } catch (Throwable) {
            // Constraint restore is best-effort; existing data may not satisfy legacy FK.
        }
    }
};
