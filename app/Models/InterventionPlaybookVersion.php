<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionPlaybookVersion extends Model
{
    protected $fillable = [
        'intervention_key', 'version', 'title', 'description',
        'evidence_source', 'evidence_grade', 'applicability', 'limitations',
        'eligible_metric_patterns', 'steps', 'guardrails', 'claims_limit',
        'status', 'published_by_user_id', 'published_at',
    ];

    protected $casts = [
        'eligible_metric_patterns' => 'array',
        'steps' => 'array',
        'guardrails' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $playbook): void {
            if ($playbook->getOriginal('status') === 'published') {
                throw new \LogicException('Published intervention playbook versions are immutable.');
            }
        });
        static::deleting(function (self $playbook): void {
            if ($playbook->status === 'published') {
                throw new \LogicException('Published intervention playbook versions are immutable.');
            }
        });
    }
}
