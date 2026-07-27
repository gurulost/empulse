<?php

return [
    'audit_hash_key' => env('AUDIT_HASH_KEY', env('APP_KEY')),
    'required_tables' => [
        'migrations',
        'jobs',
        'cache',
        'operational_heartbeats',
    ],
    'require_process_heartbeats' => env(
        'REQUIRE_PROCESS_HEARTBEATS',
        env('APP_ENV') === 'production'
    ),
    'heartbeat_max_age_seconds' => (int) env('HEARTBEAT_MAX_AGE_SECONDS', 180),
];
