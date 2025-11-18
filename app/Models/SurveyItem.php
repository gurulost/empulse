<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_version_id',
        'survey_page_id',
        'survey_section_id',
        'qid',
        'type',
        'question',
        'scale_config',
        'response_config',
        'display_logic',
        'metadata',
        'sort_order',
    ];

    protected $casts = [
        'scale_config' => 'array',
        'response_config' => 'array',
        'display_logic' => 'array',
        'metadata' => 'array',
    ];

    public function version()
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function page()
    {
        return $this->belongsTo(SurveyPage::class, 'survey_page_id');
    }

    public function section()
    {
        return $this->belongsTo(SurveySection::class, 'survey_section_id');
    }

    public function options()
    {
        return $this->hasMany(SurveyOption::class);
    }

    public function optionSource()
    {
        return $this->hasOne(SurveyOptionSource::class);
    }
}
