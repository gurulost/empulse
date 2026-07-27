<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticFinding extends Model
{
    private const MUTABLE_DECISION_FIELDS = [
        'status',
        'decided_by_user_id',
        'decided_at',
        'updated_at',
    ];

    protected $fillable = [
        'public_id', 'company_id', 'survey_wave_id', 'survey_wave_cycle_id',
        'metric_registry_version_id', 'metric_id', 'cohort_key', 'cohort_snapshot',
        'evidence_snapshot', 'evidence_hash', 'interpretation', 'limits', 'status',
        'created_by_user_id', 'decided_by_user_id', 'decided_at',
    ];

    protected $casts = [
        'cohort_snapshot' => 'array',
        'evidence_snapshot' => 'array',
        'decided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $finding): void {
            $changed = array_keys($finding->getDirty());
            if (array_diff($changed, self::MUTABLE_DECISION_FIELDS) !== []) {
                throw new \LogicException('Finding evidence snapshots are immutable after capture.');
            }
        });
        static::deleting(fn () => throw new \LogicException('Diagnostic findings are append-only.'));
    }

    /**
     * @return HasMany<LeadershipAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(LeadershipAction::class);
    }
}
