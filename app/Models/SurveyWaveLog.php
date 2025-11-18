<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyWaveLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_wave_id',
        'user_id',
        'status',
        'message',
    ];

    public function wave()
    {
        return $this->belongsTo(SurveyWave::class, 'survey_wave_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
