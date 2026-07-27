<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class ActionMeasurementPlan extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'leadership_action_id', 'baseline_wave_id',
        'followup_wave_id', 'metric_id', 'baseline_instrument_hash',
        'baseline_metric_hash', 'target_direction', 'minimum_meaningful_change',
        'audience_definition', 'status', 'created_by_user_id',
    ];

    protected $casts = [
        'minimum_meaningful_change' => 'float',
        'audience_definition' => 'array',
    ];

    /**
     * @return BelongsTo<SurveyWave, $this>
     */
    public function followupWave(): BelongsTo
    {
        return $this->belongsTo(SurveyWave::class, 'followup_wave_id');
    }

    /**
     * @return HasMany<ActionOutcome, $this>
     */
    public function outcomes(): HasMany
    {
        return $this->hasMany(ActionOutcome::class);
    }

    protected static function booted(): void
    {
        static::updating(function (ActionMeasurementPlan $plan): void {
            $allowed = ['followup_wave_id', 'status', 'updated_at'];
            if (array_diff(array_keys($plan->getDirty()), $allowed) !== []) {
                throw new LogicException(
                    'Predeclared measurement definitions are immutable after creation.'
                );
            }
        });
        static::deleting(fn () => throw new LogicException('Measurement plans are append-only.'));
    }
}
