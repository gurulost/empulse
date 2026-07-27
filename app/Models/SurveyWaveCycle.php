<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyWaveCycle extends Model
{
    protected $fillable = [
        'survey_wave_id',
        'sequence',
        'status',
        'instrument_hash',
        'metric_definition_hash',
        'metric_registry_version_id',
        'audience_hash',
        'audience_count',
        'frozen_at',
        'dispatched_at',
        'due_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'audience_count' => 'integer',
        'frozen_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function audienceMembers()
    {
        return $this->hasMany(SurveyWaveAudienceMember::class);
    }
}
