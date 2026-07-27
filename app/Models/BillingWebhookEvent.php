<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingWebhookEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'company_id',
        'event_type',
        'payload_hash',
        'stripe_created_at',
        'status',
        'processed_at',
        'error',
    ];

    protected $casts = [
        'stripe_created_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
