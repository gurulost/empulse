<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PulseVariantVersion extends Model
{
    protected $fillable = [
        'variant_key', 'version', 'title', 'purpose', 'metric_registry_version_id',
        'metric_id', 'question_ids', 'estimated_minutes',
        'minimum_days_between_invites', 'maximum_pulses_per_90_days',
        'claims_limit', 'definition_hash', 'status', 'published_by_user_id',
        'published_at',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'published_at' => 'datetime',
        'estimated_minutes' => 'integer',
        'minimum_days_between_invites' => 'integer',
        'maximum_pulses_per_90_days' => 'integer',
    ];
}
