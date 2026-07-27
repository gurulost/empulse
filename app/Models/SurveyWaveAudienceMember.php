<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyWaveAudienceMember extends Model
{
    protected $fillable = [
        'survey_wave_cycle_id',
        'user_id',
        'organization_membership_id',
        'organization_unit_id',
        'role',
        'snapshot',
        'inclusion_reason',
    ];

    protected $casts = [
        'role' => 'integer',
        'snapshot' => 'array',
    ];
}
