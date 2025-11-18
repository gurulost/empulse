<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'instrument_id',
        'version',
        'title',
        'created_utc',
        'is_active',
        'source_note',
        'meta',
    ];

    protected $casts = [
        'created_utc' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function scalePresets()
    {
        return $this->hasMany(SurveyScalePreset::class);
    }

    public function pages()
    {
        return $this->hasMany(SurveyPage::class);
    }
}
