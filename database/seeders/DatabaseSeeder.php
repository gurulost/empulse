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
        // 1. Create Company
        $company = Companies::create([
            'title' => 'Acme Corp',
            'manager' => 'Manager User',
            'manager_email' => 'manager@acme.com',
        ]);

        // 2. Create Users
        $password = Hash::make('password');

        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@workfit.com',
            'password' => $password,
            'role' => 0,
            'company_id' => null,
            'is_admin' => 1,
        ]);

        // Company Manager
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@acme.com',
            'password' => $password,
            'role' => 1,
            'company_id' => $company->id,
            'tariff' => 1, // Premium
            'company' => 1,
        ]);

        // Chief
        User::create([
            'name' => 'Chief User',
            'email' => 'chief@acme.com',
            'password' => $password,
            'role' => 2,
            'company_id' => $company->id,
        ]);

        // Team Lead
        User::create([
            'name' => 'Team Lead',
            'email' => 'lead@acme.com',
            'password' => $password,
            'role' => 3,
            'company_id' => $company->id,
        ]);

        // Employees
        $employees = [];
        for ($i = 1; $i <= 5; $i++) {
            $employees[] = User::create([
                'name' => "Employee $i",
                'email' => "employee$i@acme.com",
                'password' => $password,
                'role' => 4,
                'company_id' => $company->id,
            ]);
        }

        // 3. Create Survey & Version
        $survey = Survey::where('is_default', true)->orderBy('id')->first()
            ?? Survey::create([
                'title' => 'Employee Pulse (Default)',
                'is_default' => true,
                'status' => 'published',
            ]);

        $version = SurveyVersion::create([
            'instrument_id' => 'eng_v1',
            'version' => '1.0.0',
            'title' => 'Engagement Survey v1',
            'is_active' => true,
            'created_utc' => now(),
        ]);

        // 4. Create Page & Items
        $page = SurveyPage::create([
            'survey_version_id' => $version->id,
            'page_id' => 'p1',
            'title' => 'Core Drivers',
            'sort_order' => 1,
        ]);

        $items = [];
        $questions = [
            'q1' => 'I feel valued at work.',
            'q2' => 'I have the resources I need.',
            'q3' => 'My manager provides useful feedback.',
        ];

        $i = 1;
        foreach ($questions as $qid => $text) {
            $items[] = SurveyItem::create([
                'survey_version_id' => $version->id,
                'survey_page_id' => $page->id,
                'qid' => $qid,
                'type' => 'slider',
                'question' => $text,
                'scale_config' => ['min' => 1, 'max' => 5],
                'sort_order' => $i++,
            ]);
        }

        $itemsByQid = collect($items)->keyBy('qid');

        // 5. Create Completed Wave (Last Month)
        $pastWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Last Month Pulse',
            'status' => 'completed',
            'cadence' => 'manual',
            'opens_at' => now()->subMonth(),
            'due_at' => now()->subMonth()->addDays(7),
            'last_dispatched_at' => now()->subMonth(),
        ]);

        foreach ($employees as $emp) {
            $assignment = SurveyAssignment::create([
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $pastWave->id,
                'user_id' => $emp->id,
                'token' => Str::random(32),
                'status' => 'completed',
                'wave_label' => $pastWave->label,
                'last_dispatched_at' => now()->subMonth(),
                'completed_at' => now()->subMonth()->addDays(rand(1, 5)),
            ]);

            // Random responses
            $answers = [];
            foreach ($questions as $qid => $text) {
                $answers[$qid] = rand(3, 5); // Mostly positive
            }

            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'assignment_id' => $assignment->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $pastWave->id,
                'user_id' => $emp->id,
                'wave_label' => $pastWave->label,
                'submitted_at' => $assignment->completed_at,
                'duration_ms' => rand(45_000, 180_000),
            ]);

            foreach ($answers as $qid => $value) {
                $item = $itemsByQid->get($qid);
                if (!$item) {
                    continue;
                }

                SurveyAnswer::create([
                    'response_id' => $response->id,
                    'question_id' => $item->id,
                    'survey_item_id' => $item->id,
                    'question_key' => $qid,
                    'value' => (string) $value,
                    'value_numeric' => (float) $value,
                    'metadata' => [
                        'type' => $item->type,
                        'page_id' => $item->survey_page_id,
                        'section_id' => $item->survey_section_id,
                    ],
                ]);
            }
        }

        // 6. Create Active Wave (Current)
        $currentWave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Current Pulse',
            'status' => 'scheduled',
            'cadence' => 'manual',
            'opens_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        // Assign but don't complete all
        foreach ($employees as $index => $emp) {
            SurveyAssignment::create([
                'survey_id' => $survey->id,
                'survey_version_id' => $version->id,
                'survey_wave_id' => $currentWave->id,
                'user_id' => $emp->id,
                'token' => Str::random(32),
                'status' => $index < 2 ? 'completed' : 'invited', // First 2 completed
                'wave_label' => $currentWave->label,
                'last_dispatched_at' => now(),
            ]);
        }
    }
}
