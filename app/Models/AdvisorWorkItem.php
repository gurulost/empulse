<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorWorkItem extends Model
{
    protected $fillable = [
        'public_id',
        'company_id',
        'diagnostic_finding_id',
        'leadership_action_id',
        'kind',
        'priority',
        'status',
        'assigned_to_user_id',
        'due_at',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'due_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Companies, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Companies::class, 'company_id');
    }

    /**
     * @return BelongsTo<DiagnosticFinding, $this>
     */
    public function finding(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFinding::class, 'diagnostic_finding_id');
    }

    /**
     * @return BelongsTo<LeadershipAction, $this>
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(LeadershipAction::class, 'leadership_action_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
