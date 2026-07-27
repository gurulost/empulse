<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Explicit application capabilities
    |--------------------------------------------------------------------------
    |
    | These mappings are the temporary authorization authority while the
    | durable organization_memberships model is introduced. Routes name the
    | capability they require; a generic "non-employee is admin" grant is not
    | permitted.
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
            'billing.manage',
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
