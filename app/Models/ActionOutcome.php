<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionOutcome extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'action_measurement_plan_id', 'followup_wave_id',
        'result', 'evaluation_snapshot', 'evaluation_hash', 'interpretation',
        'causality_limit', 'evaluated_by_user_id', 'evaluated_at',
    ];

    protected $casts = [
        'evaluation_snapshot' => 'array',
        'evaluated_at' => 'datetime',
    ];
}
