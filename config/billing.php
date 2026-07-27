<?php

return [
    'catalog' => [
        'starter' => [
            'name' => 'Starter',
            'stripe_price' => env('STRIPE_PRICE_STARTER'),
            'price_cents' => env('BILLING_PRICE_STARTER_CENTS'),
            'description' => 'A governed WorkFit baseline and leadership action cycle.',
            'checkout_enabled' => true,
            'features' => ['baseline_waves', 'analytics', 'reports', 'action_loop'],
            'limits' => ['active_respondents' => 100],
        ],
        'pulse' => [
            'name' => 'Pulse',
            'stripe_price' => env('STRIPE_PRICE_PULSE'),
            'price_cents' => env('BILLING_PRICE_PULSE_CENTS'),
            'description' => 'Recurring governed Pulse measurement and longitudinal learning.',
            'checkout_enabled' => true,
            'features' => ['baseline_waves', 'recurring_waves', 'analytics', 'reports', 'action_loop', 'governed_followup_pulses'],
            'limits' => ['active_respondents' => 500],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'stripe_price' => env('STRIPE_PRICE_ENTERPRISE'),
            'price_cents' => null,
            'description' => 'Contracted enterprise scope, governance, and service levels.',
            'checkout_enabled' => false,
            'features' => ['baseline_waves', 'recurring_waves', 'analytics', 'reports', 'action_loop', 'governed_followup_pulses', 'enterprise_controls'],
            'limits' => ['active_respondents' => null],
        ],
    ],
    'dispatch_statuses' => ['active', 'trialing', 'manual_grant'],
    'data_access_statuses' => ['active', 'trialing', 'manual_grant', 'past_due', 'grace', 'canceled'],
    'trial' => [
        'enabled' => false,
        'days' => 0,
    ],
    'lifecycle' => [
        'past_due_data_grace_days' => 30,
        'canceled_data_grace_days' => 30,
        'transfer_expiry_days' => 7,
    ],
    'plan_marketing' => [
        'starter' => [
            'eyebrow' => 'For a first WorkFit learning cycle',
            'summary' => 'Run a trustworthy baseline, turn eligible findings into owned action, and preserve the decision record.',
            'features' => [
                'Privacy-gated baseline findings',
                'Manual roster and team management',
                'Leadership action workspace',
                'Billing portal access',
            ],
            'cta' => 'Choose Starter',
        ],
        'pulse' => [
            'eyebrow' => 'For continuous learning',
            'summary' => 'Connect baseline opportunities to governed follow-up pulses, action history, and comparable outcome learning.',
            'features' => [
                'Everything in Starter',
                'Opportunity-specific follow-up pulses',
                'Audience fatigue and reminder controls',
                'Comparable action-outcome history',
            ],
            'cta' => 'Choose Pulse',
        ],
        'business-plan' => [
            'eyebrow' => 'For continuous learning',
            'summary' => 'Connect baseline opportunities to governed follow-up pulses, action history, and comparable outcome learning.',
            'features' => [
                'Everything in Starter',
                'Opportunity-specific follow-up pulses',
                'Audience fatigue and reminder controls',
                'Comparable action-outcome history',
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
