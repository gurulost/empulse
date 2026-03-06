<?php

return [
    'plan_marketing' => [
        'starter' => [
            'eyebrow' => 'For first deployments',
            'summary' => 'Launch the internal survey workflow, manage teams, and review baseline reports.',
            'features' => [
                'Company dashboard and reports',
                'Team management and imports',
                'One-time survey waves',
                'Billing portal access',
            ],
            'cta' => 'Choose Starter',
        ],
        'pulse' => [
            'eyebrow' => 'For recurring listening',
            'summary' => 'Run production survey waves with drip cadences, automated dispatch, and ongoing analytics.',
            'features' => [
                'Everything in Starter',
                'Weekly, monthly, and quarterly drips',
                'Wave automation and logs',
                'Production-ready billing and subscription controls',
            ],
            'cta' => 'Choose Pulse',
        ],
        'business-plan' => [
            'eyebrow' => 'For recurring listening',
            'summary' => 'Run production survey waves with drip cadences, automated dispatch, and ongoing analytics.',
            'features' => [
                'Everything in Starter',
                'Weekly, monthly, and quarterly drips',
                'Wave automation and logs',
                'Production-ready billing and subscription controls',
            ],
            'cta' => 'Choose Pulse',
        ],
    ],
    'role_labels' => [
        1 => 'Managers',
        2 => 'Chiefs',
        3 => 'Team Leads',
        4 => 'Employees',
    ],
    'default_wave_roles' => [1, 2, 3, 4],
    'success_message' => 'Billing checkout now runs through Stripe-backed subscriptions. Plan access is confirmed by Cashier and webhook events.',
];
