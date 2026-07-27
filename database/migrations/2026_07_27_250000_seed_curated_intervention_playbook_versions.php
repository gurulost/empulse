<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VERSION = '2026-07-27';

    public function up(): void
    {
        $now = now();
        $commonGuardrails = [
            'Do not expose or attempt to infer individual survey responses.',
            'Treat the intervention as a time-bounded test, not a proven causal treatment.',
            'Record who owns the change, what will change, and when progress will be reviewed.',
            'Predeclare a comparable follow-up measure before committing the action.',
            'Stop or revise the test if employee harm, retaliation risk, or material operational harm appears.',
        ];
        $claimsLimit = 'This playbook is practical guidance, not proof that the intervention will cause a measured outcome. Results must be described as observed movement with uncertainty and alternative explanations.';

        $playbooks = [
            [
                'intervention_key' => 'work_design_small_test',
                'title' => 'Run a focused work-design test',
                'description' => 'Test one concrete change to how work is structured, supported, or coordinated for a defined group.',
                'eligible_metric_patterns' => ['opportunity.*'],
                'steps' => [
                    ['title' => 'Clarify the opportunity', 'instruction' => 'Review the frozen finding with people closest to the work and document plausible explanations without identifying respondents.'],
                    ['title' => 'Choose one change', 'instruction' => 'Select one reversible change within leadership control and define the participating group.'],
                    ['title' => 'Set the learning window', 'instruction' => 'Name an owner, start date, review date, success criteria, and operational guardrails.'],
                    ['title' => 'Close the loop', 'instruction' => 'Tell employees what was heard, what will be tested, and when they will hear what was learned.'],
                ],
            ],
            [
                'intervention_key' => 'team_norms_reset',
                'title' => 'Reset one team operating norm',
                'description' => 'Co-design and test a specific team norm involving communication, decision-making, workload, or mutual support.',
                'eligible_metric_patterns' => ['culture.*'],
                'steps' => [
                    ['title' => 'Name the friction', 'instruction' => 'Use facilitated discussion and the aggregate finding to describe the work pattern, not individual blame.'],
                    ['title' => 'Draft one observable norm', 'instruction' => 'Define what people will do differently, in which situations, and how exceptions will be handled.'],
                    ['title' => 'Trial and inspect', 'instruction' => 'Run the norm for a bounded period and collect qualitative operational feedback.'],
                    ['title' => 'Measure and decide', 'instruction' => 'Use the predeclared follow-up measure plus operational evidence to retain, revise, or stop the norm.'],
                ],
            ],
            [
                'intervention_key' => 'leadership_listening_cycle',
                'title' => 'Run a leadership listening cycle',
                'description' => 'Validate an aggregate indicator through structured listening and commit to a visible, bounded response.',
                'eligible_metric_patterns' => ['indicator.*'],
                'steps' => [
                    ['title' => 'Prepare neutral prompts', 'instruction' => 'Ask what helps, what gets in the way, and what is within leadership control without soliciting respondent identities.'],
                    ['title' => 'Listen across the cohort', 'instruction' => 'Use multiple voluntary forums or representatives so one voice is not treated as the cohort.'],
                    ['title' => 'Select a bounded response', 'instruction' => 'Choose one action that can be owned, observed, and reviewed within the measurement window.'],
                    ['title' => 'Publish the response', 'instruction' => 'Explain what was heard, what will change, what will not change, and why.'],
                ],
            ],
            [
                'intervention_key' => 'impact_line_of_sight',
                'title' => 'Strengthen line of sight to impact',
                'description' => 'Test a concrete way for employees to see how their work connects to customers, communities, or organizational purpose.',
                'eligible_metric_patterns' => ['impact.*'],
                'steps' => [
                    ['title' => 'Identify the disconnect', 'instruction' => 'Document where the connection between daily work and intended impact becomes unclear.'],
                    ['title' => 'Choose an evidence source', 'instruction' => 'Select credible customer, beneficiary, quality, or outcome evidence without exaggerating attribution.'],
                    ['title' => 'Embed one practice', 'instruction' => 'Test a recurring practice that connects team decisions and work products to that evidence.'],
                    ['title' => 'Review usefulness', 'instruction' => 'Ask whether the practice improved clarity and decision quality before deciding whether to continue it.'],
                ],
            ],
        ];

        foreach ($playbooks as $playbook) {
            DB::table('intervention_playbook_versions')->updateOrInsert(
                [
                    'intervention_key' => $playbook['intervention_key'],
                    'version' => self::VERSION,
                ],
                [
                    'title' => $playbook['title'],
                    'description' => $playbook['description'],
                    'eligible_metric_patterns' => json_encode($playbook['eligible_metric_patterns'], JSON_THROW_ON_ERROR),
                    'steps' => json_encode($playbook['steps'], JSON_THROW_ON_ERROR),
                    'guardrails' => json_encode($commonGuardrails, JSON_THROW_ON_ERROR),
                    'claims_limit' => $claimsLimit,
                    'status' => 'published',
                    'published_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('intervention_playbook_versions')
            ->where('version', self::VERSION)
            ->whereIn('intervention_key', [
                'work_design_small_test',
                'team_norms_reset',
                'leadership_listening_cycle',
                'impact_line_of_sight',
            ])
            ->delete();
    }
};
