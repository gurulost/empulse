<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionMeasurementPlan extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'leadership_action_id', 'baseline_wave_id',
        'followup_wave_id', 'metric_id', 'baseline_instrument_hash',
        'baseline_metric_hash', 'target_direction', 'minimum_meaningful_change',
        'audience_definition', 'status', 'created_by_user_id',
    ];

    protected $casts = [
        'minimum_meaningful_change' => 'float',
        'audience_definition' => 'array',
    ];
}
