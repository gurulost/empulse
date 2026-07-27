<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class LeadershipAction extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'diagnostic_finding_id',
        'intervention_playbook_version_id', 'title', 'hypothesis', 'planned_change',
        'success_criteria', 'guardrails', 'owner_user_id', 'created_by_user_id',
        'status', 'starts_on', 'target_date', 'committed_at', 'completed_at',
    ];

    protected $casts = [
        'success_criteria' => 'array',
        'guardrails' => 'array',
        'starts_on' => 'date',
        'target_date' => 'date',
        'committed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<DiagnosticFinding, $this>
     */
    public function finding(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFinding::class, 'diagnostic_finding_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<ActionMeasurementPlan, $this>
     */
    public function measurementPlans(): HasMany
    {
        return $this->hasMany(ActionMeasurementPlan::class);
    }

    protected static function booted(): void
    {
        static::updating(function (LeadershipAction $action): void {
            $allowed = ['status', 'committed_at', 'completed_at', 'updated_at'];
            $changed = array_keys($action->getDirty());
            if (array_diff($changed, $allowed) !== []) {
                throw new LogicException(
                    'Leadership action plans are immutable after creation; record lifecycle changes through status events.'
                );
            }
        });
        static::deleting(fn () => throw new LogicException('Leadership actions are append-only.'));
    }
}
