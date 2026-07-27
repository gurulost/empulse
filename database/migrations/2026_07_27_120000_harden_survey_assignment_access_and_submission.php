<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->string('token')->nullable()->change();
            $table->string('token_hash', 64)->nullable()->unique()->after('token');
            $table->timestamp('token_expires_at')->nullable()->after('token_hash');
            $table->timestamp('token_revoked_at')->nullable()->after('token_expires_at');
            $table->unsignedBigInteger('draft_revision')->default(0)->after('draft_answers');
        });

        DB::table('survey_assignments')
            ->whereNotNull('token')
            ->orderBy('id')
            ->each(function ($assignment): void {
                DB::table('survey_assignments')
                    ->where('id', $assignment->id)
                    ->update([
                        'token_hash' => hash('sha256', $assignment->token),
                        'token_expires_at' => now()->addDays(14),
                        'token' => null,
                    ]);
            });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unique('assignment_id', 'survey_responses_assignment_unique');
        });

        Schema::table('survey_answers', function (Blueprint $table) {
            $table->unique(
                ['response_id', 'question_key'],
                'survey_answers_response_question_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropUnique('survey_answers_response_question_unique');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique('survey_responses_assignment_unique');
        });

        Schema::table('survey_assignments', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->dropColumn([
                'token_hash',
                'token_expires_at',
                'token_revoked_at',
                'draft_revision',
            ]);
            $table->string('token')->nullable(false)->change();
        });
    }
};
