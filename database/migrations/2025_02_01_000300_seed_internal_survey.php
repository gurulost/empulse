<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        if (DB::table('surveys')->where('is_default', true)->exists()) {
            return;
        }

        $surveyId = DB::table('surveys')->insertGetId([
            'title' => 'Employee Pulse (Internal)',
            'description' => 'Placeholder assessment until the full survey is imported.',
            'is_default' => true,
            'status' => 'published',
            'metadata' => json_encode([
                'version' => 'placeholder',
                'created_by_migration' => true,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $questions = [
            [
                'key' => 'engagement_score',
                'title' => 'How supported do you feel by your immediate team?',
                'question_type' => 'scale',
                'sort_order' => 1,
            ],
            [
                'key' => 'enablement_score',
                'title' => 'Do you have the tools and skills you need to succeed?',
                'question_type' => 'scale',
                'sort_order' => 2,
            ],
            [
                'key' => 'alignment_score',
                'title' => 'How aligned do you feel with company goals?',
                'question_type' => 'scale',
                'sort_order' => 3,
            ],
            [
                'key' => 'culture_score',
                'title' => 'How would you rate the culture in your department?',
                'question_type' => 'scale',
                'sort_order' => 4,
            ],
            [
                'key' => 'open_feedback',
                'title' => 'Anything else you want leadership to know?',
                'question_type' => 'text',
                'sort_order' => 5,
            ],
        ];

        foreach ($questions as $question) {
            DB::table('survey_questions')->insert([
                'survey_id' => $surveyId,
                'title' => $question['title'],
                'key' => $question['key'],
                'question_type' => $question['question_type'],
                'metrics' => json_encode([]),
                'sort_order' => $question['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $userIds = DB::table('users')->pluck('id');
        foreach ($userIds as $userId) {
            DB::table('survey_assignments')->insert([
                'survey_id' => $surveyId,
                'user_id' => $userId,
                'token' => (string) Str::uuid(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        $survey = DB::table('surveys')->where('is_default', true)->first();

        if ($survey) {
            DB::table('survey_questions')->where('survey_id', $survey->id)->delete();
            DB::table('survey_assignments')->where('survey_id', $survey->id)->delete();
            DB::table('surveys')->where('id', $survey->id)->delete();
        }
    }
};
