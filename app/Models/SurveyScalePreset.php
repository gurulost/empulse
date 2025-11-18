<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyScalePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_version_id',
        'preset_key',
        'config',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function version()
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }
}
