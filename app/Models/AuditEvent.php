<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string|null $company_title
 * @property-read string|null $actor_name
 */
class AuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'stream_key',
        'sequence',
        'company_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'changes',
        'metadata',
        'previous_hash',
        'event_hash',
        'occurred_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'changes' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Audit events are append-only.'));
    }
}
