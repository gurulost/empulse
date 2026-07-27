<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyAcknowledgment extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'survey_assignment_id',
        'policy_version',
        'policy_hash',
        'acknowledged_at',
        'source',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];
}
