<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationMembership extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'status',
        'valid_from',
        'valid_to',
        'created_by',
    ];

    protected $casts = [
        'role' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function currentAssignment()
    {
        return $this->hasOne(OrganizationAssignment::class, 'membership_id')
            ->whereNull('valid_to')
            ->latestOfMany();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
