<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RosterImport extends Model
{
    protected $fillable = [
        'public_id',
        'company_id',
        'created_by',
        'original_filename',
        'source_sha256',
        'source_csv',
        'status',
        'total_rows',
        'create_count',
        'update_count',
        'reactivate_count',
        'deactivate_count',
        'unchanged_count',
        'error_count',
        'confirmation_token_hash',
        'confirmation_expires_at',
        'parsed_at',
        'committed_at',
        'rows_purged_at',
        'failed_at',
        'failure_summary',
    ];

    protected $hidden = [
        'source_csv',
        'confirmation_token_hash',
    ];

    protected $casts = [
        'source_csv' => 'encrypted',
        'total_rows' => 'integer',
        'create_count' => 'integer',
        'update_count' => 'integer',
        'reactivate_count' => 'integer',
        'deactivate_count' => 'integer',
        'unchanged_count' => 'integer',
        'error_count' => 'integer',
        'confirmation_expires_at' => 'datetime',
        'parsed_at' => 'datetime',
        'committed_at' => 'datetime',
        'rows_purged_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return HasMany<RosterImportRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(RosterImportRow::class)->orderBy('row_number');
    }

    /**
     * @return BelongsTo<Companies, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Companies::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
