<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryContact extends Model
{
    protected $attributes = [
        'status' => 'deliverable',
        'failure_count' => 0,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'email',
        'status',
        'failure_count',
        'suppressed_at',
        'suppression_reason',
    ];

    protected $casts = [
        'failure_count' => 'integer',
        'suppressed_at' => 'datetime',
    ];
}
