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
        'content_hash',
        'published_at',
        'published_by',
        'publication_status',
        'change_summary',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'created_utc' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
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
