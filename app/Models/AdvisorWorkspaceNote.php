<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorWorkspaceNote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'public_id',
        'company_id',
        'advisor_company_grant_id',
        'author_user_id',
        'visibility',
        'body',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Advisor workspace notes are append-only.'));
        static::deleting(fn () => throw new \LogicException('Advisor workspace notes are append-only.'));
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function advisorGrant(): BelongsTo
    {
        return $this->belongsTo(AdvisorCompanyGrant::class, 'advisor_company_grant_id');
    }
}
