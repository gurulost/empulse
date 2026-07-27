<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingCatalogVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'definition_hash',
        'definition',
        'status',
        'published_by_user_id',
        'effective_at',
        'created_at',
    ];

    protected $casts = [
        'definition' => 'array',
        'effective_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Billing catalog versions are immutable.'));
        static::deleting(fn () => throw new \LogicException('Billing catalog versions are immutable.'));
    }
}
