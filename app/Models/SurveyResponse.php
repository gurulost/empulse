<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'survey_version_id',
        'survey_wave_id',
        'survey_wave_cycle_id',
        'survey_wave_audience_member_id',
        'assignment_id',
        'user_id',
        'wave_label',
        'meta',
        'submitted_at',
        'duration_ms',
        'cohort_snapshot',
        'privacy_policy_version',
        'metric_registry_version_id',
        'metric_definition_hash',
    ];

    protected $casts = [
        'meta' => 'array',
        'submitted_at' => 'datetime',
        'duration_ms' => 'integer',
        'cohort_snapshot' => 'array',
    ];

    public function assignment()
    {
        return $this->belongsTo(SurveyAssignment::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyVersion()
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function surveyWave()
    {
        return $this->belongsTo(SurveyWave::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyAnswer::class, 'response_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
