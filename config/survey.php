<?php

return [
    'work_content_attributes' => [
        'WCA_REL' => ['label' => 'Building relationships with other people.', 'indicator' => 'relationships'],
        'WCA_TRAIN' => ['label' => 'Receiving training in valuable new skills.', 'indicator' => 'growth'],
        'WCA_LEARN' => ['label' => 'Learning valuable new knowledge.', 'indicator' => 'growth'],
        'WCA_BETTER' => ['label' => 'Doing things that help me be a better person.', 'indicator' => 'purpose'],
        'WCA_PAY' => ['label' => 'Pay and benefits.', 'indicator' => 'rewards'],
        'WCA_SCHED' => ['label' => 'Schedule or working location.', 'indicator' => 'flexibility'],
        'WCA_DREAM' => ['label' => 'Working toward my dream role or position.', 'indicator' => 'progression'],
        'WCA_CLIENTS' => ['label' => 'Having a direct impact on helping clients/customers.', 'indicator' => 'impact'],
        'WCA_PROGRESS' => ['label' => 'Making a direct contribution to our organization.', 'indicator' => 'impact'],
        'WCA_TEAM' => ['label' => 'Being part of a close-knit team.', 'indicator' => 'relationships'],
        'WCA_TASKS' => ['label' => 'Completing tasks that I find meaningful.', 'indicator' => 'purpose'],
        'WCA_PROJECTS' => ['label' => 'Making important contributions to meaningful projects.', 'indicator' => 'purpose'],
    ],

    'indicators' => [
        'relationships' => [
            'label' => 'Relationships & Team',
            'attributes' => ['WCA_REL', 'WCA_TEAM'],
            'weight' => 1.0,
        ],
        'growth' => [
            'label' => 'Learning & Growth',
            'attributes' => ['WCA_TRAIN', 'WCA_LEARN'],
            'weight' => 1.0,
        ],
        'purpose' => [
            'label' => 'Purpose & Meaning',
            'attributes' => ['WCA_BETTER', 'WCA_TASKS', 'WCA_PROJECTS'],
            'weight' => 1.0,
        ],
        'rewards' => [
            'label' => 'Rewards',
            'attributes' => ['WCA_PAY'],
            'weight' => 1.0,
        ],
        'flexibility' => [
            'label' => 'Flexibility',
            'attributes' => ['WCA_SCHED'],
            'weight' => 1.0,
        ],
        'progression' => [
            'label' => 'Progression',
            'attributes' => ['WCA_DREAM'],
            'weight' => 1.0,
        ],
        'impact' => [
            'label' => 'Impact',
            'attributes' => ['WCA_CLIENTS', 'WCA_PROGRESS'],
            'weight' => 1.0,
        ],
    ],

    'team_culture' => [
        'positive' => [
            'TC_01',
            'TC_02',
            'TC_03',
            'TC_PS_05',
            'TC_PS_06',
            'TC_PS_08',
            'TC_PS_11',
            'WEL_01',
            'WEL_02',
            'WEL_03',
            'WEL_TCE_04',
            'WEL_TCE_05',
        ],
        'negative' => [
            'TC_04',
            'TC_PS_07',
            'TC_PS_09',
            'TC_PS_10',
            'TC_ADD_12',
            'TC_ADD_13',
            'TC_ADD_14',
            'WEL_TCE_06',
        ],
    ],

    'team_culture_evaluation' => [
        'scale' => [
            'min' => 1,
            'max' => 9,
        ],
        // Mirrors the product formula grouping for Team Culture section clusters.
        'dimensions' => [
            'team_core' => [
                'label' => 'Team Culture Core',
                'weight' => 0.34,
                'questions' => ['TC_01', 'TC_02', 'TC_03', 'TC_04'],
            ],
            'psychological_safety' => [
                'label' => 'Psychological Safety',
                'weight' => 0.33,
                'questions' => ['TC_PS_05', 'TC_PS_06', 'TC_PS_07', 'TC_PS_08', 'TC_PS_09', 'TC_PS_10', 'TC_PS_11', 'TC_ADD_12', 'TC_ADD_13', 'TC_ADD_14'],
            ],
            'ethics_leadership' => [
                'label' => 'Ethics & Leadership',
                'weight' => 0.33,
                'questions' => ['WEL_01', 'WEL_02', 'WEL_03', 'WEL_TCE_04', 'WEL_TCE_05', 'WEL_TCE_06'],
            ],
        ],
    ],

    'temperature' => [
        'weights' => [
            'indicator' => 0.65,
            'culture' => 0.35,
        ],
    ],

    'impact_series' => [
        'positive' => [
            'IMPACT_PN_01',
            'IMPACT_SIZE_04',
        ],
        'importance' => [
            'IMPACT_PN_02',
            'IMPACT_SIZE_05',
        ],
        'desire' => [
            'IMPACT_PN_03',
            'IMPACT_SIZE_06',
        ],
    ],

    'validation' => [
        'strict_server_validation' => env('SURVEY_STRICT_VALIDATION_ENABLED', false),
        'default_required_types' => [
            'slider',
            'single_select',
            'single_select_text',
            'dropdown',
            'multi_select',
            'number_integer',
        ],
        'default_max_text_length' => 5000,
    ],

    'automation' => [
        'cadence_windows' => [
            'weekly' => 60 * 24 * 7,
            'monthly' => 60 * 24 * 30,
            'quarterly' => 60 * 24 * 90,
        ],
        'processing_timeout_minutes' => env('SURVEY_WAVE_PROCESSING_TIMEOUT_MINUTES', 30),
        'manual_one_shot' => true,
        'drip_tariffs' => [1],
        'tariff_labels' => [
            0 => 'Starter',
            1 => 'Pulse (Drip Enabled)',
            'default' => 'Starter',
        ],
        'billing_statuses' => ['active', 'trialing', 'manual-premium'],
        'billing_labels' => [
            'active' => 'Active subscription',
            'trialing' => 'Trialing',
            'past_due' => 'Past due',
            'unpaid' => 'Unpaid',
            'canceled' => 'Canceled',
            'incomplete_expired' => 'Incomplete',
            'manual-premium' => 'Manual premium (grandfathered)',
            'none' => 'Not subscribed',
        ],
    ],
];
