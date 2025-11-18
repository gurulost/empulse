<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyOptionSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_item_id',
        'kind',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(SurveyItem::class, 'survey_item_id');
    }
}
