<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_item_id',
        'value',
        'label',
        'exclusive',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'exclusive' => 'boolean',
        'meta' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(SurveyItem::class, 'survey_item_id');
    }
}
