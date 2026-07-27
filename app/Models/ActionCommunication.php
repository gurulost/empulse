<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ActionCommunication extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'leadership_action_id', 'audience',
        'channel', 'message', 'status', 'created_by_user_id',
        'published_by_user_id', 'published_at',
    ];

    protected $casts = ['published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Published action communications are immutable.'));
        static::deleting(fn () => throw new LogicException('Action communications are append-only.'));
    }
}
