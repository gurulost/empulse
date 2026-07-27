<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountInvitation extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'invited_by',
        'email',
        'role',
        'token_hash',
        'status',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    protected $casts = [
        'role' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
