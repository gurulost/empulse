<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadershipAction extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'diagnostic_finding_id',
        'intervention_playbook_version_id', 'title', 'hypothesis', 'planned_change',
        'success_criteria', 'guardrails', 'owner_user_id', 'created_by_user_id',
        'status', 'starts_on', 'target_date', 'committed_at', 'completed_at',
    ];

    protected $casts = [
        'success_criteria' => 'array',
        'guardrails' => 'array',
        'starts_on' => 'date',
        'target_date' => 'date',
        'committed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function finding()
    {
        return $this->belongsTo(DiagnosticFinding::class, 'diagnostic_finding_id');
    }

    public function measurementPlans()
    {
        return $this->hasMany(ActionMeasurementPlan::class);
    }
}
