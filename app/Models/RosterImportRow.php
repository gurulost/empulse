<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterImportRow extends Model
{
    protected $fillable = [
        'roster_import_id',
        'row_number',
        'external_id',
        'name',
        'email',
        'role',
        'department',
        'supervisor_external_id',
        'desired_status',
        'action',
        'target_user_id',
        'target_fingerprint',
        'changes',
        'errors',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'role' => 'integer',
        'changes' => 'array',
        'errors' => 'array',
    ];

    /**
     * @return BelongsTo<RosterImport, $this>
     */
    public function rosterImport(): BelongsTo
    {
        return $this->belongsTo(RosterImport::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
