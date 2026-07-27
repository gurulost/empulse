<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionRun extends Model
{
    protected $fillable = [
        'public_id',
        'initiated_by_user_id',
        'dry_run',
        'status',
        'plan_hash',
        'plan',
        'result',
        'executed_at',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'plan' => 'array',
        'result' => 'array',
        'executed_at' => 'datetime',
    ];
}
