<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveySection extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_page_id',
        'section_id',
        'title',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(SurveyPage::class, 'survey_page_id');
    }

    public function items()
    {
        return $this->hasMany(SurveyItem::class, 'survey_section_id');
    }
}
