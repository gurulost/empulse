<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetricRegistryVersion extends Model
{
    protected $fillable = [
        'registry_key',
        'version',
        'definition_hash',
        'definition',
        'status',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'definition' => 'array',
        'published_at' => 'datetime',
    ];
}
