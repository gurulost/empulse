<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'response_id',
        'question_id',
        'survey_item_id',
        'question_key',
        'value',
        'value_numeric',
        'metadata',
    ];

    protected $casts = [
        'value_numeric' => 'float',
        'metadata' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class);
    }

    public function item()
    {
        return $this->belongsTo(SurveyItem::class, 'survey_item_id');
    }

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class);
    }
}
