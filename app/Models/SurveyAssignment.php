<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'survey_version_id',
        'survey_wave_id',
        'user_id',
        'token',
        'status',
        'wave_label',
        'draft_answers',
        'last_autosaved_at',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'draft_answers' => 'array',
        'last_autosaved_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function response()
    {
        return $this->hasOne(SurveyResponse::class, 'assignment_id');
    }
}
