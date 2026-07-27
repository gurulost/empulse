<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationEntitlementVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'version',
        'billing_catalog_version_id',
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
        'recorded_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Entitlement history is immutable.'));
        static::deleting(fn () => throw new \LogicException('Entitlement history is immutable.'));
    }
}
