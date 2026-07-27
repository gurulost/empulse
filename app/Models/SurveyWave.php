<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyWave extends Model
{
    use HasFactory;

    protected $attributes = [
        'target_roles' => '[1,2,3,4]',
    ];

    protected $fillable = [
        'company_id',
        'survey_id',
        'survey_version_id',
        'kind',
        'status',
        'cadence',
        'label',
        'target_roles',
        'opens_at',
        'due_at',
        'last_dispatched_at',
        'pulse_variant_version_id',
        'action_measurement_plan_id',
        'measurement_purpose',
        'reminder_limit',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'opens_at' => 'datetime',
        'due_at' => 'datetime',
        'last_dispatched_at' => 'datetime',
        'reminder_limit' => 'integer',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyVersion()
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function company()
    {
        return $this->belongsTo(Companies::class, 'company_id');
    }

    public function assignments()
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    public function cycles()
    {
        return $this->hasMany(SurveyWaveCycle::class);
    }

    public function pulseVariant()
    {
        return $this->belongsTo(PulseVariantVersion::class, 'pulse_variant_version_id');
    }
}
