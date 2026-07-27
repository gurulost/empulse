<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingAdminTransferRequest extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'from_user_id', 'to_user_id',
        'requested_by_user_id', 'decided_by_user_id', 'status',
        'reason', 'expires_at', 'decided_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'decided_at' => 'datetime',
    ];
}
