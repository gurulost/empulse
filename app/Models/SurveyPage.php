<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_version_id',
        'page_id',
        'title',
        'attribute_label',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function version()
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function sections()
    {
        return $this->hasMany(SurveySection::class);
    }

    public function items()
    {
        return $this->hasMany(SurveyItem::class);
    }
}
