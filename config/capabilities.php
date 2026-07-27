<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Explicit application capabilities
    |--------------------------------------------------------------------------
    |
    | Routes name the capability they require; a generic "non-employee is
    | admin" grant is not permitted. Billing is intentionally excluded here:
    | organization_billing_admins is its explicit authorization authority.
    |
    */
    'roles' => [
        0 => [
            'analytics.view',
            'workfit.admin',
            'survey-builder.manage',
            'privacy.manage',
            'actions.advisor',
            'actions.manage',
            'actions.view',
        ],
        1 => [
            'analytics.view',
            'team.manage',
            'survey.manage',
            'survey-waves.manage',
            'actions.manage',
            'actions.view',
            'advisor-access.manage',
        ],
        2 => [
            'analytics.view',
            'team.manage',
            'actions.view',
        ],
        3 => [
            'analytics.view',
            'team.manage',
            'actions.view',
        ],
        4 => [
            'employee.dashboard',
        ],
    ],

    'workfit_admin' => [
        'analytics.view',
        'workfit.admin',
        'survey-builder.manage',
        'privacy.manage',
        'actions.advisor',
        'actions.manage',
        'actions.view',
    ],
];
