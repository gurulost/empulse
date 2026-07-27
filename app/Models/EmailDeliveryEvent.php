<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailDeliveryEvent extends Model
{
    public $timestamps = false;

    protected $attributes = [
        'provider' => 'brevo',
    ];

    protected $fillable = [
        'delivery_contact_id',
        'survey_assignment_id',
        'idempotency_key',
        'message_kind',
        'status',
        'provider',
        'provider_message_id',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Delivery events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Delivery events are append-only.'));
    }
}
