<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationBillingAdmin extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'status',
        'approved_by',
        'approved_at',
        'revoked_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
