<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationEntitlement extends Model
{
    protected $fillable = [
        'company_id',
        'plan_key',
        'status',
        'source',
        'stripe_subscription_id',
        'stripe_price_id',
        'features',
        'limits',
        'starts_at',
        'trial_ends_at',
        'grace_ends_at',
        'ends_at',
        'last_stripe_event_at',
        'last_reconciled_at',
        'version',
        'billing_catalog_version_id',
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_stripe_event_at' => 'datetime',
        'last_reconciled_at' => 'datetime',
        'version' => 'integer',
    ];
}
