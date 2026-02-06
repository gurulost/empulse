<?php

namespace Database\Seeders;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyAnswer;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $company = Companies::firstOrCreate(
            ['manager_email' => 'manager@acme.com'],
            ['title' => 'Acme Corp', 'manager' => 'Manager User']
        );

        $password = Hash::make('password');

        User::firstOrCreate(
            ['email' => 'admin@workfit.com'],
            ['name' => 'Super Admin', 'password' => $password, 'role' => 0, 'company_id' => null, 'is_admin' => 1]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@acme.com'],
            ['name' => 'Manager User', 'password' => $password, 'role' => 1, 'company_id' => $company->id, 'tariff' => 1, 'company' => 1]
        );

        User::firstOrCreate(
            ['email' => 'chief@acme.com'],
            ['name' => 'Chief User', 'password' => $password, 'role' => 2, 'company_id' => $company->id]
        );

        User::firstOrCreate(
            ['email' => 'lead@acme.com'],
            ['name' => 'Team Lead', 'password' => $password, 'role' => 3, 'company_id' => $company->id]
        );

        $employees = [];
        for ($i = 1; $i <= 5; $i++) {
            $employees[] = User::firstOrCreate(
                ['email' => "employee$i@acme.com"],
                ['name' => "Employee $i", 'password' => $password, 'role' => 4, 'company_id' => $company->id]
            );
        }

        // 3. Create Survey & Version
        $survey = Survey::where('is_default', true)->orderBy('id')->first()
            ?? Survey::create([
                'title' => 'Employee Pulse (Default)',
                'is_default' => true,
                'status' => 'published',
            ]);

        $version = SurveyVersion::firstOrCreate(
            ['instrument_id' => 'eng_v1', 'version' => '1.0.0'],
            ['title' => 'Engagement Survey v1', 'is_active' => true, 'created_utc' => now()]
        );

        $page = SurveyPage::firstOrCreate(
            ['survey_version_id' => $version->id, 'page_id' => 'p1'],
            ['title' => 'Core Drivers', 'sort_order' => 1]
        );

        $items = [];
        $questions = [
            'q1' => 'I feel valued at work.',
            'q2' => 'I have the resources I need.',
            'q3' => 'My manager provides useful feedback.',
        ];

        $i = 1;
        foreach ($questions as $qid => $text) {
            $items[] = SurveyItem::firstOrCreate(
                ['survey_version_id' => $version->id, 'qid' => $qid],
                ['survey_page_id' => $page->id, 'type' => 'slider', 'question' => $text, 'scale_config' => ['min' => 1, 'max' => 5], 'sort_order' => $i++]
            );
        }

        $itemsByQid = collect($items)->keyBy('qid');

        $pastWave = SurveyWave::firstOrCreate(
            ['company_id' => $company->id, 'label' => 'Last Month Pulse'],
            ['survey_id' => $survey->id, 'survey_version_id' => $version->id, 'kind' => 'full', 'status' => 'completed', 'cadence' => 'manual', 'opens_at' => now()->subMonth(), 'due_at' => now()->subMonth()->addDays(7), 'last_dispatched_at' => now()->subMonth()]
        );

        foreach ($employees as $emp) {
            $assignment = SurveyAssignment::firstOrCreate(
                ['survey_wave_id' => $pastWave->id, 'user_id' => $emp->id],
                ['survey_id' => $survey->id, 'survey_version_id' => $version->id, 'token' => Str::random(32), 'status' => 'completed', 'wave_label' => $pastWave->label, 'last_dispatched_at' => now()->subMonth(), 'completed_at' => now()->subMonth()->addDays(rand(1, 5))]
            );

            $answers = [];
            foreach ($questions as $qid => $text) {
                $answers[$qid] = rand(3, 5);
            }

            $response = SurveyResponse::firstOrCreate(
                ['survey_wave_id' => $pastWave->id, 'user_id' => $emp->id],
                ['survey_id' => $survey->id, 'assignment_id' => $assignment->id, 'survey_version_id' => $version->id, 'wave_label' => $pastWave->label, 'submitted_at' => $assignment->completed_at, 'duration_ms' => rand(45_000, 180_000)]
            );

            foreach ($answers as $qid => $value) {
                $item = $itemsByQid->get($qid);
                if (!$item) {
                    continue;
                }

                SurveyAnswer::firstOrCreate(
                    ['response_id' => $response->id, 'question_key' => $qid],
                    ['question_id' => $item->id, 'survey_item_id' => $item->id, 'value' => (string) $value, 'value_numeric' => (float) $value, 'metadata' => ['type' => $item->type, 'page_id' => $item->survey_page_id, 'section_id' => $item->survey_section_id]]
                );
            }
        }

        $currentWave = SurveyWave::firstOrCreate(
            ['company_id' => $company->id, 'label' => 'Current Pulse'],
            ['survey_id' => $survey->id, 'survey_version_id' => $version->id, 'kind' => 'full', 'status' => 'scheduled', 'cadence' => 'manual', 'opens_at' => now(), 'due_at' => now()->addDays(7)]
        );

        foreach ($employees as $index => $emp) {
            SurveyAssignment::firstOrCreate(
                ['survey_wave_id' => $currentWave->id, 'user_id' => $emp->id],
                ['survey_id' => $survey->id, 'survey_version_id' => $version->id, 'token' => Str::random(32), 'status' => $index < 2 ? 'completed' : 'invited', 'wave_label' => $currentWave->label, 'last_dispatched_at' => now()]
            );
        }
    }
}
