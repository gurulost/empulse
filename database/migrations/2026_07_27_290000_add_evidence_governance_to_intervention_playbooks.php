<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intervention_playbook_versions', function (Blueprint $table) {
            $table->text('evidence_source')
                ->default('WorkFit practice synthesis; independent methodology approval pending.')
                ->after('description');
            $table->string('evidence_grade', 80)
                ->default('practice-informed-unvalidated')
                ->after('evidence_source');
            $table->text('applicability')
                ->default('Use only for a matching, reliable aggregate finding and a bounded leadership-controlled test.')
                ->after('evidence_grade');
            $table->text('limitations')
                ->default('No causal effect is established. Local context, implementation fidelity, operational evidence, and a comparable follow-up measure are required.')
                ->after('applicability');
        });

        $governance = [
            'work_design_small_test' => [
                'applicability' => 'Reliable aggregate opportunity findings about work content, structure, support, or coordination where leadership can run a reversible, bounded test.',
                'limitations' => 'Practice-informed guidance only. It does not establish why the opportunity exists or predict that a work-design change will improve the measure.',
            ],
            'team_norms_reset' => [
                'applicability' => 'Reliable aggregate culture findings where a defined team can co-design and observe a specific operating norm without exposing respondent identities.',
                'limitations' => 'Practice-informed guidance only. Norm changes may not address structural, staffing, leadership, or psychological-safety causes of the finding.',
            ],
            'leadership_listening_cycle' => [
                'applicability' => 'Reliable aggregate indicator findings that need contextual interpretation before leadership selects a bounded response.',
                'limitations' => 'Practice-informed guidance only. Voluntary listening can be affected by participation and power dynamics and must not be treated as representative causal proof.',
            ],
            'impact_line_of_sight' => [
                'applicability' => 'Reliable aggregate impact findings where credible customer, beneficiary, quality, or outcome evidence can be connected to daily work.',
                'limitations' => 'Practice-informed guidance only. A clearer narrative may not change work conditions, and organizational contribution must not be overstated as direct attribution.',
            ],
        ];

        foreach ($governance as $key => $fields) {
            DB::table('intervention_playbook_versions')
                ->where('intervention_key', $key)
                ->update([
                    ...$fields,
                    'evidence_source' => 'WorkFit practice synthesis; independent methodology approval pending. No causal trial evidence is represented.',
                    'evidence_grade' => 'practice-informed-unvalidated',
                    'updated_at' => now(),
                ]);
        }

        $now = now();
        DB::table('intervention_playbook_versions')->updateOrInsert(
            [
                'intervention_key' => 'investigate_first',
                'version' => '2026-07-27',
            ],
            [
                'title' => 'Investigate before choosing an intervention',
                'description' => 'Use structured, privacy-safe inquiry to test plausible explanations before leadership commits to a change.',
                'evidence_source' => 'WorkFit practice synthesis; independent methodology approval pending. No causal trial evidence is represented.',
                'evidence_grade' => 'practice-informed-unvalidated',
                'applicability' => 'Any reliable aggregate opportunity, indicator, culture, or impact finding whose context, mechanism, or leadership-controlled response remains uncertain.',
                'limitations' => 'Inquiry can reduce uncertainty but cannot prove causality or represent every employee. Participation, power dynamics, and alternative explanations must remain visible.',
                'eligible_metric_patterns' => json_encode([
                    'opportunity.*',
                    'indicator.*',
                    'culture.*',
                    'impact.*',
                ], JSON_THROW_ON_ERROR),
                'steps' => json_encode([
                    ['title' => 'Name the uncertainty', 'instruction' => 'Record what the aggregate finding establishes, what it does not establish, and the plausible explanations that need testing.'],
                    ['title' => 'Choose privacy-safe inquiry', 'instruction' => 'Use voluntary, aggregate-safe listening or operational evidence without requesting respondent identities or individual answers.'],
                    ['title' => 'Compare explanations', 'instruction' => 'Document converging and conflicting evidence, affected groups, and material unknowns.'],
                    ['title' => 'Decide the next step', 'instruction' => 'Choose a bounded action, gather more evidence, or record why no action is warranted yet.'],
                ], JSON_THROW_ON_ERROR),
                'guardrails' => json_encode([
                    'Do not expose or attempt to infer individual survey responses.',
                    'Do not present listening participation as representative without evidence.',
                    'Keep plausible alternative explanations visible.',
                    'Record a decision and rationale even when leadership chooses not to act yet.',
                ], JSON_THROW_ON_ERROR),
                'claims_limit' => 'This playbook is an uncertainty-reduction option, not evidence that a particular cause or intervention is correct.',
                'status' => 'published',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('intervention_playbook_versions')
            ->where('intervention_key', 'investigate_first')
            ->where('version', '2026-07-27')
            ->delete();

        Schema::table('intervention_playbook_versions', function (Blueprint $table) {
            $table->dropColumn([
                'evidence_source',
                'evidence_grade',
                'applicability',
                'limitations',
            ]);
        });
    }
};
