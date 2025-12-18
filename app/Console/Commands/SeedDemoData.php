<?php

namespace App\Console\Commands;

use App\Models\Companies;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed
        {--employees=120 : Number of employees for Acme (includes employee1-3)}
        {--months=6 : Number of past months to generate survey waves for (plus current month)}
        {--import-instrument : Import survey_instrument.json into survey_* schema (if missing)}
        {--force : Do not prompt for confirmation}';

    protected $description = 'Populate the app with realistic synthetic demo data (Acme Corp + waves + responses) for showing off full functionality.';

    public function handle(): int
    {
        if (!(bool) $this->option('force')) {
            if (!$this->confirm('This will add/update a lot of demo data in your current database. Continue?', true)) {
                return self::SUCCESS;
            }
        }

        $employeeCount = max(3, (int) $this->option('employees'));
        $months = max(1, (int) $this->option('months'));

        $this->info('Seeding demo data...');

        return DB::transaction(function () use ($employeeCount, $months) {
            $company = $this->ensureAcmeCompany();
            $this->ensurePlans();
            $users = $this->ensureCoreAccounts($company);
            $this->ensureCompanyStructure($company, $employeeCount, $users);

            [$survey, $version] = $this->ensureSurveyArtifacts((bool) $this->option('import-instrument'));

            $this->seedSurveyWavesAndResponses(
                $company->id,
                $survey->id,
                $version->id,
                $months
            );

            $this->info('Demo data seeded successfully.');
            $this->line('Logins (password: password):');
            $this->line('- admin@workfit.com (Super Admin)');
            $this->line('- manager@acme.com (Manager)');
            $this->line('- chief@acme.com (Chief)');
            $this->line('- lead@acme.com (Team Lead)');
            $this->line('- employee1@acme.com (Employee)');
            $this->line('- employee2@acme.com (Employee)');
            $this->line('- employee3@acme.com (Employee)');

            return self::SUCCESS;
        });
    }

    protected function ensureAcmeCompany(): Companies
    {
        $company = Companies::where('manager_email', 'manager@acme.com')->first();

        if (!$company) {
            $company = Companies::create([
                'title' => 'Acme Corp',
                'manager' => 'Manager User',
                'manager_email' => 'manager@acme.com',
            ]);
        } else {
            $company->update([
                'title' => 'Acme Corp',
                'manager' => $company->manager ?: 'Manager User',
            ]);
        }

        return $company;
    }

    protected function ensurePlans(): void
    {
        if (DB::table('plans')->count() > 0) {
            return;
        }

        DB::table('plans')->insert([
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'stripe_plan' => 'price_starter_demo',
                'price' => 0,
                'description' => 'Demo starter plan (no drip scheduling).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pulse',
                'slug' => 'pulse',
                'stripe_plan' => 'price_pulse_demo',
                'price' => 19900,
                'description' => 'Demo Pulse plan (drip enabled).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * @return array{admin: User, manager: User, chief: User, lead: User, employee1: User, employee2: User, employee3: User}
     */
    protected function ensureCoreAccounts(Companies $company): array
    {
        $password = Hash::make('password');

        $admin = User::updateOrCreate(
            ['email' => 'admin@workfit.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'role' => 0,
                'company_id' => null,
                'company_title' => null,
                'company' => 0,
                'tariff' => 1,
                'is_admin' => 1,
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@acme.com'],
            [
                'name' => 'Manager User',
                'password' => $password,
                'role' => 1,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'company' => 1,
                'tariff' => 1,
            ]
        );

        $chief = User::updateOrCreate(
            ['email' => 'chief@acme.com'],
            [
                'name' => 'Chief User',
                'password' => $password,
                'role' => 2,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'company' => 0,
                'tariff' => 1,
            ]
        );

        $lead = User::updateOrCreate(
            ['email' => 'lead@acme.com'],
            [
                'name' => 'Team Lead',
                'password' => $password,
                'role' => 3,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'company' => 0,
                'tariff' => 1,
            ]
        );

        $employee1 = User::updateOrCreate(
            ['email' => 'employee1@acme.com'],
            [
                'name' => 'Employee 1',
                'password' => $password,
                'role' => 4,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'company' => 0,
                'tariff' => 1,
            ]
        );

        $employee2 = User::updateOrCreate(
            ['email' => 'employee2@acme.com'],
            [
                'name' => 'Employee 2',
                'password' => $password,
                'role' => 4,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'company' => 0,
                'tariff' => 1,
            ]
        );

        $employee3 = User::updateOrCreate(
            ['email' => 'employee3@acme.com'],
            [
                'name' => 'Employee 3',
                'password' => $password,
                'role' => 4,
                'company_id' => $company->id,
                'company_title' => $company->title,
                'company' => 0,
                'tariff' => 1,
            ]
        );

        // Mark manager subscription as active-ish (demo) so wave scheduling is not paused.
        $this->ensureManagerSubscription($manager);

        return compact('admin', 'manager', 'chief', 'lead', 'employee1', 'employee2', 'employee3');
    }

    protected function ensureManagerSubscription(User $manager): void
    {
        $hasSubscription = DB::table('subscriptions')
            ->where('user_id', $manager->id)
            ->exists();

        if ($hasSubscription) {
            return;
        }

        DB::table('subscriptions')->insert([
            'user_id' => $manager->id,
            'name' => 'default',
            'stripe_id' => 'sub_demo_' . Str::random(8),
            'stripe_status' => 'active',
            'stripe_price' => 'price_pulse_demo',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function ensureCompanyStructure(Companies $company, int $employeeCount, array $coreUsers): void
    {
        $departments = [
            'Engineering',
            'Sales',
            'Marketing',
            'Customer Success',
            'Operations',
            'HR',
        ];

        foreach ($departments as $department) {
            $exists = DB::table('company_department')
                ->where('company_id', $company->id)
                ->where('title', $department)
                ->exists();

            if (!$exists) {
                DB::table('company_department')->insert([
                    'company_id' => $company->id,
                    'title' => $department,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Ensure core workers exist (used by analytics + filters).
        $this->upsertCompanyWorker($company->id, $company->title, $coreUsers['manager'], 'Operations', $coreUsers['chief']->name);
        $this->upsertCompanyWorker($company->id, $company->title, $coreUsers['chief'], 'Operations', $coreUsers['manager']->name);
        $this->upsertCompanyWorker($company->id, $company->title, $coreUsers['lead'], 'Engineering', $coreUsers['chief']->name);
        $this->upsertCompanyWorker($company->id, $company->title, $coreUsers['employee1'], 'Engineering', $coreUsers['lead']->name);
        $this->upsertCompanyWorker($company->id, $company->title, $coreUsers['employee2'], 'Sales', $coreUsers['chief']->name);
        $this->upsertCompanyWorker($company->id, $company->title, $coreUsers['employee3'], 'Marketing', $coreUsers['chief']->name);

        // Add a few more team leads to make the team filter meaningful.
        $teamLeadsByDepartment = [
            'Engineering' => [$coreUsers['lead']],
            'Sales' => [],
            'Marketing' => [],
            'Customer Success' => [],
            'Operations' => [],
            'HR' => [],
        ];

        foreach (['Sales', 'Marketing', 'Customer Success', 'Operations', 'HR'] as $dept) {
            $email = Str::slug("lead-{$dept}") . '@acme.com';
            $lead = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "{$dept} Lead",
                    'password' => Hash::make('password'),
                    'role' => 3,
                    'company_id' => $company->id,
                    'company_title' => $company->title,
                    'company' => 0,
                    'tariff' => 1,
                ]
            );
            $teamLeadsByDepartment[$dept][] = $lead;
            $this->upsertCompanyWorker($company->id, $company->title, $lead, $dept, $coreUsers['chief']->name);
        }

        // Generate additional employees (employee4..employeeN).
        for ($i = 4; $i <= $employeeCount; $i++) {
            $email = "employee{$i}@acme.com";
            $dept = $departments[($i - 1) % count($departments)];
            $lead = $teamLeadsByDepartment[$dept][array_key_first($teamLeadsByDepartment[$dept])] ?? $coreUsers['lead'];

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Employee {$i}",
                    'password' => Hash::make('password'),
                    'role' => 4,
                    'company_id' => $company->id,
                    'company_title' => $company->title,
                    'company' => 0,
                    'tariff' => 1,
                ]
            );

            $this->upsertCompanyWorker($company->id, $company->title, $user, $dept, $lead->name);
        }
    }

    protected function upsertCompanyWorker(int $companyId, string $companyTitle, User $user, ?string $department, ?string $supervisor): void
    {
        DB::table('company_worker')->updateOrInsert(
            ['email' => $user->email],
            [
                'company_id' => $companyId,
                'company_title' => $companyTitle,
                'name' => $user->name,
                'department' => $department,
                'supervisor' => $supervisor,
                'role' => (int) $user->role,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * @return array{0: Survey, 1: SurveyVersion}
     */
    protected function ensureSurveyArtifacts(bool $importInstrument): array
    {
        $survey = Survey::where('is_default', true)->orderBy('id')->first();
        if (!$survey) {
            $survey = Survey::create([
                'title' => 'Employee Pulse (Default)',
                'is_default' => true,
                'status' => 'published',
            ]);
        }

        $version = SurveyVersion::where('is_active', true)->orderByDesc('id')->first();

        $hasWorkContent = DB::table('survey_items')->where('qid', 'WCA_REL_A')->exists();

        if ($importInstrument && !$hasWorkContent) {
            $path = base_path('survey_instrument.json');
            if (!is_file($path)) {
                $this->warn('survey_instrument.json not found; skipping instrument import.');
            } else {
                $this->info('Importing survey instrument (this may take a moment)...');
                Artisan::call('survey:import', [
                    'path' => $path,
                    '--activate' => true,
                ]);
                $version = SurveyVersion::where('is_active', true)->orderByDesc('id')->first();
            }
        }

        if (!$version) {
            $version = SurveyVersion::create([
                'instrument_id' => 'demo_v1',
                'version' => '1.0.0',
                'title' => 'Demo Survey v1',
                'is_active' => true,
                'created_utc' => now(),
            ]);
        }

        return [$survey, $version];
    }

    protected function seedSurveyWavesAndResponses(int $companyId, int $surveyId, int $versionId, int $months): void
    {
        $users = User::where('company_id', $companyId)->whereIn('role', [1, 2, 3, 4])->get();
        if ($users->isEmpty()) {
            return;
        }

        $itemsByQid = DB::table('survey_items')
            ->where('survey_version_id', $versionId)
            ->select('id', 'qid')
            ->get()
            ->keyBy('qid');

        $wcaBases = array_keys((array) config('survey.work_content_attributes', []));
        $positiveCulture = (array) config('survey.team_culture.positive', []);
        $negativeCulture = (array) config('survey.team_culture.negative', []);
        $impactSeries = (array) config('survey.impact_series', []);

        $departmentProfiles = [
            'Engineering' => ['current' => 7.2, 'gap' => 1.2, 'culture' => 7.8],
            'Sales' => ['current' => 6.4, 'gap' => 1.6, 'culture' => 7.1],
            'Marketing' => ['current' => 6.8, 'gap' => 1.3, 'culture' => 7.4],
            'Customer Success' => ['current' => 7.0, 'gap' => 1.1, 'culture' => 7.6],
            'Operations' => ['current' => 6.0, 'gap' => 1.9, 'culture' => 6.9],
            'HR' => ['current' => 6.6, 'gap' => 1.4, 'culture' => 7.2],
        ];

        for ($i = $months; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $label = $month->format('M Y') . ' Pulse';
            $opensAt = $month->copy()->addDays(3)->setTime(9, 0);
            $dueAt = $month->copy()->endOfMonth()->setTime(17, 0);
            $status = $dueAt->isPast() ? 'completed' : 'scheduled';

            $waveId = $this->firstOrCreateWave(
                $companyId,
                $surveyId,
                $versionId,
                $label,
                $opensAt,
                $dueAt,
                $status
            );

            // Completion rate: past waves high, current wave low.
            $completionRate = $i === 0 ? 0.25 : 0.8;

            foreach ($users as $user) {
                $worker = DB::table('company_worker')
                    ->where('company_id', $companyId)
                    ->where('email', $user->email)
                    ->first();

                $dept = $worker->department ?? 'Operations';
                $profile = $departmentProfiles[$dept] ?? ['current' => 6.5, 'gap' => 1.5, 'culture' => 7.0];

                $shouldComplete = ((crc32($user->email . '|' . $label) % 1000) / 1000) < $completionRate;

                $assignment = SurveyAssignment::firstOrCreate(
                    [
                        'survey_id' => $surveyId,
                        'user_id' => $user->id,
                        'survey_wave_id' => $waveId,
                    ],
                    [
                        'token' => (string) Str::uuid(),
                        'status' => $shouldComplete ? 'completed' : 'invited',
                        'due_at' => $dueAt,
                        'survey_version_id' => $versionId,
                        'wave_label' => $label,
                        'last_dispatched_at' => $opensAt->copy()->addHours(2),
                        'dispatch_count' => 1,
                        'completed_at' => $shouldComplete ? $dueAt->copy()->subDays(random_int(0, 7)) : null,
                    ]
                );

                if (!$assignment->survey_version_id || !$assignment->wave_label) {
                    $assignment->update([
                        'survey_version_id' => $assignment->survey_version_id ?: $versionId,
                        'wave_label' => $assignment->wave_label ?: $label,
                    ]);
                }

                if (!$shouldComplete) {
                    continue;
                }

                if ($assignment->response) {
                    continue;
                }

                $response = SurveyResponse::create([
                    'survey_id' => $surveyId,
                    'survey_version_id' => $versionId,
                    'survey_wave_id' => $waveId,
                    'assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                    'wave_label' => $label,
                    'submitted_at' => $assignment->completed_at ?: $dueAt->copy()->subDays(1),
                    'duration_ms' => random_int(45_000, 240_000),
                ]);

                $answers = [];

                // Work content attributes (drives indicators + gap chart).
                foreach ($wcaBases as $base) {
                    $current = $this->clampInt($this->normal($profile['current'], 1.2), 1, 10);
                    $ideal = $this->clampInt($current + $this->normal($profile['gap'], 0.7), 1, 10);
                    $desire = $this->clampInt($ideal - $current + $this->normal(3, 1.2), 1, 9);

                    $answers["{$base}_A"] = $current;
                    $answers["{$base}_B"] = $ideal;
                    $answers["{$base}_C"] = $desire;
                }

                // Team culture (drives culture pulse + scatter).
                foreach ($positiveCulture as $qid) {
                    $answers[$qid] = $this->clampInt($this->normal($profile['culture'], 1.0), 1, 10);
                }
                foreach ($negativeCulture as $qid) {
                    $answers[$qid] = $this->clampInt($this->normal(4.2, 1.3), 1, 10);
                }

                // Impact snapshot.
                foreach ($impactSeries as $series => $qids) {
                    foreach ($qids as $qid) {
                        $base = $series === 'positive' ? 6.8 : ($series === 'importance' ? 7.4 : 5.2);
                        $answers[$qid] = $this->clampInt($this->normal($base, 1.4), 1, 10);
                    }
                }

                $this->insertSurveyAnswers($response->id, $answers, $itemsByQid);
            }
        }
    }

    protected function firstOrCreateWave(
        int $companyId,
        int $surveyId,
        int $versionId,
        string $label,
        Carbon $opensAt,
        Carbon $dueAt,
        string $status
    ): int {
        $wave = DB::table('survey_waves')
            ->where('company_id', $companyId)
            ->where('survey_id', $surveyId)
            ->where('survey_version_id', $versionId)
            ->where('label', $label)
            ->first();

        if ($wave) {
            DB::table('survey_waves')->where('id', $wave->id)->update([
                'opens_at' => $opensAt,
                'due_at' => $dueAt,
                'status' => $status,
                'cadence' => 'manual',
                'updated_at' => now(),
            ]);
            return (int) $wave->id;
        }

        return (int) DB::table('survey_waves')->insertGetId([
            'company_id' => $companyId,
            'survey_id' => $surveyId,
            'survey_version_id' => $versionId,
            'kind' => 'full',
            'status' => $status,
            'cadence' => 'manual',
            'label' => $label,
            'opens_at' => $opensAt,
            'due_at' => $dueAt,
            'last_dispatched_at' => $opensAt->copy()->addHours(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function insertSurveyAnswers(int $responseId, array $answersByQid, $itemsByQid): void
    {
        $rows = [];
        foreach ($answersByQid as $qid => $value) {
            $item = $itemsByQid->get($qid);
            if (!$item) {
                continue;
            }

            $numeric = is_numeric($value) ? (float) $value : null;

            $rows[] = [
                'response_id' => $responseId,
                'question_id' => $item->id,
                'survey_item_id' => $item->id,
                'question_key' => $qid,
                'value' => (string) $value,
                'value_numeric' => $numeric,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('survey_answers')->insert($chunk);
        }
    }

    protected function normal(float $mean, float $stdDev): float
    {
        // Box–Muller transform.
        $u1 = max(1e-9, mt_rand() / mt_getrandmax());
        $u2 = max(1e-9, mt_rand() / mt_getrandmax());
        $z0 = sqrt(-2.0 * log($u1)) * cos(2.0 * pi() * $u2);
        return $mean + $z0 * $stdDev;
    }

    protected function clampInt(float $value, int $min, int $max): int
    {
        return (int) max($min, min($max, round($value)));
    }
}
