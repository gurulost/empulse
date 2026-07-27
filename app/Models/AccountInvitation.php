<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'delivery_token',
        'delivery_idempotency_key',
        'delivery_status',
        'delivery_attempts',
        'delivery_last_attempt_at',
        'provider_message_id',
        'delivery_error',
        'delivered_at',
    ];

    protected $casts = [
        'role' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'delivery_token' => 'encrypted',
        'delivery_attempts' => 'integer',
        'delivery_last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
        'delivery_token',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
