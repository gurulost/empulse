<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorCompanyGrant extends Model
{
    protected $fillable = [
        'company_id',
        'advisor_user_id',
        'approved_by_user_id',
        'status',
        'purpose',
        'valid_from',
        'valid_until',
        'revoked_at',
        'revoked_by_user_id',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_user_id');
    }
}
