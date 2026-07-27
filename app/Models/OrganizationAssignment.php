<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationAssignment extends Model
{
    protected $fillable = [
        'membership_id',
        'organization_unit_id',
        'reports_to_membership_id',
        'status',
        'unresolved_reason',
        'valid_from',
        'valid_to',
        'created_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];
}
