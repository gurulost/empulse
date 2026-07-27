<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionPlaybookVersion extends Model
{
    protected $fillable = [
        'intervention_key', 'version', 'title', 'description',
        'eligible_metric_patterns', 'steps', 'guardrails', 'claims_limit',
        'status', 'published_by_user_id', 'published_at',
    ];

    protected $casts = [
        'eligible_metric_patterns' => 'array',
        'steps' => 'array',
        'guardrails' => 'array',
        'published_at' => 'datetime',
    ];
}
