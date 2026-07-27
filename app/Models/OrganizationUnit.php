<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationUnit extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'stable_key',
        'type',
        'name',
        'status',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];
}
