<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalHeartbeat extends Model
{
    protected $fillable = ['process', 'instance_id', 'last_seen_at', 'metadata'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
    ];
}
