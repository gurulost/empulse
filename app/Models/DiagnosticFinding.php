<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticFinding extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'survey_wave_id', 'survey_wave_cycle_id',
        'metric_registry_version_id', 'metric_id', 'cohort_key', 'cohort_snapshot',
        'evidence_snapshot', 'evidence_hash', 'interpretation', 'limits', 'status',
        'created_by_user_id', 'decided_by_user_id', 'decided_at',
    ];

    protected $casts = [
        'cohort_snapshot' => 'array',
        'evidence_snapshot' => 'array',
        'decided_at' => 'datetime',
    ];

    public function actions()
    {
        return $this->hasMany(LeadershipAction::class);
    }
}
