<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalHold extends Model
{
    protected $fillable = [
        'company_id',
        'subject_user_id',
        'created_by_user_id',
        'released_by_user_id',
        'scope',
        'reason',
        'starts_at',
        'ends_at',
        'released_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query
            ->whereNull('released_at')
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
