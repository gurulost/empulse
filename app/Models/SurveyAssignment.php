<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyAssignment extends Model
{
    use HasFactory;

    protected ?string $plainTextToken = null;

    protected $attributes = [
        'invite_status' => 'pending',
    ];

    protected $fillable = [
        'survey_id',
        'survey_version_id',
        'survey_wave_id',
        'survey_wave_cycle_id',
        'survey_wave_audience_member_id',
        'user_id',
        'token',
        'token_hash',
        'token_expires_at',
        'token_revoked_at',
        'status',
        'wave_label',
        'draft_answers',
        'draft_revision',
        'last_autosaved_at',
        'last_dispatched_at',
        'dispatch_count',
        'invited_at',
        'invite_status',
        'invite_error',
        'due_at',
        'completed_at',
        'cohort_snapshot',
        'reminder_count',
        'last_reminded_at',
        'privacy_policy_version',
        'privacy_acknowledged_at',
    ];

    protected $casts = [
        'draft_answers' => 'array',
        'draft_revision' => 'integer',
        'token_expires_at' => 'datetime',
        'token_revoked_at' => 'datetime',
        'last_autosaved_at' => 'datetime',
        'last_dispatched_at' => 'datetime',
        'invited_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'cohort_snapshot' => 'array',
        'reminder_count' => 'integer',
        'last_reminded_at' => 'datetime',
        'privacy_acknowledged_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
        'token_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (SurveyAssignment $assignment): void {
            $plainTextToken = $assignment->getAttributes()['token'] ?? null;
            if (! is_string($plainTextToken) || $plainTextToken === '') {
                $plainTextToken = Str::random(64);
            }

            $assignment->plainTextToken = $plainTextToken;
            $assignment->token = null;
            $assignment->token_hash = self::hashToken($plainTextToken);
            $assignment->token_expires_at ??= now()->addDays(14);
        });
    }

    public static function findByAccessToken(string $plainTextToken): ?self
    {
        return self::query()
            ->where('token_hash', self::hashToken($plainTextToken))
            ->first();
    }

    public function rotateAccessToken(?\DateTimeInterface $expiresAt = null): string
    {
        $plainTextToken = Str::random(64);

        $this->forceFill([
            'token' => null,
            'token_hash' => self::hashToken($plainTextToken),
            'token_expires_at' => $expiresAt ?: now()->addDays(14),
            'token_revoked_at' => null,
        ])->save();

        $this->plainTextToken = $plainTextToken;

        return $plainTextToken;
    }

    public function plainTextToken(): ?string
    {
        return $this->plainTextToken;
    }

    public function getTokenAttribute(mixed $storedValue): ?string
    {
        return $this->plainTextToken;
    }

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function surveyVersion()
    {
        return $this->belongsTo(SurveyVersion::class, 'survey_version_id');
    }

    public function surveyWave()
    {
        return $this->belongsTo(SurveyWave::class);
    }

    public function surveyWaveCycle()
    {
        return $this->belongsTo(SurveyWaveCycle::class);
    }

    public function audienceMember()
    {
        return $this->belongsTo(
            SurveyWaveAudienceMember::class,
            'survey_wave_audience_member_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function response()
    {
        return $this->hasOne(SurveyResponse::class, 'assignment_id');
    }
}
