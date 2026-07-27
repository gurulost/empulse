<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Respondent data promise
    |--------------------------------------------------------------------------
    |
    | This is the enforceable default for the pre-deployment release candidate.
    | Changes require product/privacy approval, a new version, updated disclosure,
    | and regression review. Empulse does not claim anonymous collection.
    |
    */
    'policy' => [
        'version' => env('RESPONDENT_POLICY_VERSION', '2026-07-27.1'),
        'title' => 'Empulse respondent data promise',
        'purpose' => 'Help your organization understand work experience patterns and record leadership follow-through.',
        'identity' => 'Empulse uses your identity to secure your assigned survey, save progress, prevent duplicate submissions, and place your response in the correct historical cohort.',
        'visibility' => 'Your employer receives aggregate results only when the applicable sample and privacy rules are met. Individual answers are not available through normal customer roles.',
        'workfit_access' => 'Authorized WorkFit privacy operators may access limited records only for verified support, legal, security, or data-rights work. Access is attributable and audited.',
        'retention' => 'Submitted analytical evidence is retained for up to seven years unless a verified legal or contractual rule requires a different period. Drafts and delivery records have shorter schedules.',
        'progress' => 'Your progress is saved while you complete the survey. Draft answers are removed after submission or after the assignment retention window.',
        'rights' => 'You may request access, correction, or erasure review through the designated privacy contact. Legal holds and the need to preserve de-identified analytical integrity may limit deletion.',
        'contact' => env('PRIVACY_CONTACT_EMAIL', 'privacy@workfitdx.com'),
    ],

    'reporting' => [
        'minimum_company_n' => (int) env('PRIVACY_MINIMUM_COMPANY_N', 5),
        'minimum_subgroup_n' => (int) env('PRIVACY_MINIMUM_SUBGROUP_N', 7),
        'minimum_completion_rate' => (float) env('PRIVACY_MINIMUM_COMPLETION_RATE', 0.40),
        'complementary_suppression' => true,
        'customer_raw_answer_access' => false,
    ],

    'retention_days' => [
        'expired_drafts' => 30,
        'expired_invitations' => 30,
        'delivery_events' => 400,
        'onboarding_events' => 400,
        'roster_import_rows' => 30,
        'submitted_evidence' => 2555,
        'audit_events' => null,
        'billing_records' => null,
    ],

    /*
    | Non-analytical/free-text/demographic answers are removed during approved
    | erasure. The retained analytical keys remain attached only to a disabled,
    | pseudonymized subject so historical aggregate calculations reproduce.
    */
    'analytical_question_prefixes' => [
        'WCA_',
        'TC_',
        'WEL_',
        'IMPACT_',
    ],
];
