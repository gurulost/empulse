<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataSubjectRequest extends Model
{
    protected $fillable = [
        'public_id',
        'company_id',
        'subject_user_id',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'type',
        'status',
        'reason',
        'requested_changes',
        'result_summary',
        'identity_verified_at',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'requested_changes' => 'array',
        'result_summary' => 'array',
        'identity_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }
}
