<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int|string|null $event_count
 * @property-read string|null $first_event_at
 * @property-read string|null $last_event_at
 */
class OrganizationUsageEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'idempotency_key',
        'metric',
        'quantity',
        'unit',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Usage events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Usage events are append-only.'));
    }
}
