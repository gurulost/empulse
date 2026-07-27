<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
