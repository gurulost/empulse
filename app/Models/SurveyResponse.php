<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'survey_version_id',
        'survey_wave_id',
        'assignment_id',
        'user_id',
        'wave_label',
        'meta',
        'submitted_at',
        'duration_ms',
    ];

    protected $casts = [
        'meta' => 'array',
        'submitted_at' => 'datetime',
        'duration_ms' => 'integer',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
