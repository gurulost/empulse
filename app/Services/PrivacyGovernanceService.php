<?php

namespace App\Services;

use App\Models\DataSubjectRequest;
use App\Models\DeliveryContact;
use App\Models\LegalHold;
use App\Models\PrivacyAcknowledgment;
use App\Models\SurveyAnswer;
use App\Models\SurveyAssignment;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrivacyGovernanceService
{
    public function __construct(protected AuditTrailService $audit) {}

    public function policyPayload(): array
    {
        $policy = config('privacy.policy', []);

        return [
            ...$policy,
            'hash' => $this->policyHash(),
            'anonymous' => false,
            'customer_raw_answer_access' => false,
            'minimum_company_n' => (int) config('privacy.reporting.minimum_company_n', 5),
            'minimum_subgroup_n' => (int) config('privacy.reporting.minimum_subgroup_n', 7),
        ];
    }

    public function policyHash(): string
    {
        return hash('sha256', json_encode(
            config('privacy.policy', []),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }

    public function acknowledge(SurveyAssignment $assignment): PrivacyAcknowledgment
    {
        $assignment->loadMissing('user');
        $version = (string) config('privacy.policy.version');
        $companyId = (int) $assignment->user?->company_id;
        if (! $companyId) {
            throw new \DomainException('The assignment has no organization context.');
        }

        return DB::transaction(function () use ($assignment, $version, $companyId) {
            $acknowledgment = PrivacyAcknowledgment::firstOrCreate(
                [
                    'survey_assignment_id' => $assignment->id,
                    'policy_version' => $version,
                ],
                [
                    'company_id' => $companyId,
                    'user_id' => $assignment->user_id,
                    'policy_hash' => $this->policyHash(),
                    'acknowledged_at' => now(),
                    'source' => 'survey',
                ]
            );

            $assignment->forceFill([
                'privacy_policy_version' => $version,
                'privacy_acknowledged_at' => $acknowledgment->acknowledged_at,
            ])->save();

            return $acknowledgment;
        });
    }

    public function createRequest(
        User $subject,
        string $type,
        ?User $requestedBy,
        ?string $reason = null,
        array $requestedChanges = []
    ): DataSubjectRequest {
        if (! in_array($type, ['access', 'correction', 'erasure'], true)) {
            throw new \InvalidArgumentException('Unsupported data-subject request type.');
        }
        if (! $subject->company_id) {
            throw new \DomainException('The subject has no organization context.');
        }

        $request = DataSubjectRequest::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $subject->company_id,
            'subject_user_id' => $subject->id,
            'requested_by_user_id' => $requestedBy?->id,
            'type' => $type,
            'status' => 'requested',
            'reason' => $reason,
            'requested_changes' => $requestedChanges ?: null,
        ]);

        $this->audit->record(
            'privacy.request.created',
            $requestedBy,
            (int) $subject->company_id,
            DataSubjectRequest::class,
            $request->id,
            ['type' => $type, 'status' => 'requested']
        );

        return $request;
    }

    public function verifyIdentity(DataSubjectRequest $request, User $reviewer): DataSubjectRequest
    {
        $this->assertPrivacyOperator($reviewer);
        if ($request->status !== 'requested') {
            throw new \DomainException('Only a requested case can be identity-verified.');
        }

        $request->update([
            'status' => 'identity_verified',
            'reviewed_by_user_id' => $reviewer->id,
            'identity_verified_at' => now(),
        ]);
        $this->auditStatus($request, $reviewer, 'identity_verified');

        return $request->fresh();
    }

    public function approve(DataSubjectRequest $request, User $reviewer): DataSubjectRequest
    {
        $this->assertPrivacyOperator($reviewer);
        if ($request->status !== 'identity_verified') {
            throw new \DomainException('Identity verification is required before approval.');
        }

        if ($request->type === 'erasure' && $this->hasActiveHold($request)) {
            $request->update([
                'status' => 'blocked',
                'reviewed_by_user_id' => $reviewer->id,
                'result_summary' => ['reason' => 'active_legal_hold'],
            ]);
            $this->auditStatus($request, $reviewer, 'blocked');

            return $request->fresh();
        }

        $request->update([
            'status' => 'approved',
            'reviewed_by_user_id' => $reviewer->id,
            'approved_at' => now(),
        ]);
        $this->auditStatus($request, $reviewer, 'approved');

        return $request->fresh();
    }

    public function execute(DataSubjectRequest $request, User $reviewer): array
    {
        $this->assertPrivacyOperator($reviewer);
        if ($request->status !== 'approved') {
            throw new \DomainException('The request must be approved before execution.');
        }
        if ($request->type === 'erasure' && $this->hasActiveHold($request)) {
            throw new \DomainException('An active legal hold blocks erasure.');
        }

        $result = match ($request->type) {
            'access' => $this->subjectExport($request->subject),
            'correction' => $this->applyCorrection($request),
            'erasure' => $this->pseudonymizeSubject($request),
        };

        $summary = $request->type === 'access'
            ? ['export_generated' => true, 'response_count' => count($result['responses'] ?? [])]
            : $result;

        $request->update([
            'status' => 'completed',
            'reviewed_by_user_id' => $reviewer->id,
            'result_summary' => $summary,
            'completed_at' => now(),
        ]);
        $this->auditStatus($request, $reviewer, 'completed', $summary);

        return $result;
    }

    public function subjectExport(User $subject): array
    {
        $subject->loadMissing([]);
        $responses = SurveyResponse::with('answers:id,response_id,question_key,value,value_numeric,metadata')
            ->where('user_id', $subject->id)
            ->orderBy('submitted_at')
            ->get();

        return [
            'schema_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
                'email' => $subject->email,
                'status' => $subject->status,
                'company_id' => $subject->company_id,
            ],
            'assignments' => SurveyAssignment::where('user_id', $subject->id)
                ->orderBy('id')
                ->get([
                    'id', 'survey_id', 'survey_version_id', 'survey_wave_id',
                    'status', 'due_at', 'completed_at', 'privacy_policy_version',
                    'privacy_acknowledged_at',
                ])->toArray(),
            'responses' => $responses->map(fn (SurveyResponse $response) => [
                'id' => $response->id,
                'survey_id' => $response->survey_id,
                'survey_version_id' => $response->survey_version_id,
                'survey_wave_id' => $response->survey_wave_id,
                'submitted_at' => $response->submitted_at?->toIso8601String(),
                'privacy_policy_version' => $response->privacy_policy_version,
                'answers' => $response->answers->map(fn (SurveyAnswer $answer) => [
                    'question_key' => $answer->question_key,
                    'value' => $answer->value,
                    'value_numeric' => $answer->value_numeric,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    protected function applyCorrection(DataSubjectRequest $request): array
    {
        $allowed = array_intersect_key(
            $request->requested_changes ?? [],
            array_flip(['name', 'email'])
        );
        if ($allowed === []) {
            throw new \DomainException('No approved correctable fields were supplied.');
        }
        if (isset($allowed['email'])) {
            $allowed['email'] = mb_strtolower(trim((string) $allowed['email']));
            if (! filter_var($allowed['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \DomainException('The corrected email address is invalid.');
            }
            if (User::where('email', $allowed['email'])->whereKeyNot($request->subject_user_id)->exists()) {
                throw new \DomainException('The corrected email address is already in use.');
            }
        }

        return DB::transaction(function () use ($request, $allowed) {
            $subject = User::lockForUpdate()->findOrFail($request->subject_user_id);
            $oldEmail = $subject->email;
            $subject->forceFill($allowed)->save();

            DB::table('company_worker')
                ->where('company_id', $request->company_id)
                ->where('email', $oldEmail)
                ->update(array_filter([
                    'name' => $allowed['name'] ?? null,
                    'email' => $allowed['email'] ?? null,
                ], fn ($value) => $value !== null));

            DeliveryContact::where('company_id', $request->company_id)
                ->where('user_id', $subject->id)
                ->when(isset($allowed['email']), fn ($query) => $query->update(['email' => $allowed['email']]));

            return ['corrected_fields' => array_keys($allowed)];
        });
    }

    protected function pseudonymizeSubject(DataSubjectRequest $request): array
    {
        return DB::transaction(function () use ($request) {
            $subject = User::lockForUpdate()->findOrFail($request->subject_user_id);
            $oldEmail = $subject->email;
            $alias = 'erased-'.hash_hmac('sha256', (string) $subject->id, config('app.key'));
            $email = substr($alias, 0, 48).'@privacy.invalid';

            $responseIds = SurveyResponse::where('user_id', $subject->id)->pluck('id');
            $removedAnswers = SurveyAnswer::whereIn('response_id', $responseIds)
                ->where(function ($query) {
                    foreach ((array) config('privacy.analytical_question_prefixes', []) as $prefix) {
                        $query->where('question_key', 'not like', $prefix.'%');
                    }
                })
                ->delete();

            DB::table('company_worker')
                ->where('company_id', $request->company_id)
                ->where('email', $oldEmail)
                ->update([
                    'name' => 'Erased respondent',
                    'email' => $email,
                    'status' => 'inactive',
                    'left_at' => now(),
                ]);

            DeliveryContact::where('company_id', $request->company_id)
                ->where('user_id', $subject->id)
                ->update([
                    'email' => $email,
                    'status' => 'suppressed',
                    'suppressed_at' => now(),
                    'suppression_reason' => 'privacy_erasure',
                ]);

            $subject->forceFill([
                'name' => 'Erased respondent',
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'google_id' => null,
                'fb_id' => null,
                'image' => null,
                'remember_token' => null,
                'status' => 'inactive',
                'left_at' => $subject->left_at ?: now(),
                'privacy_erased_at' => now(),
            ])->save();
            $subject->tokens()->delete();

            return [
                'identity_pseudonymized' => true,
                'non_analytical_answers_removed' => $removedAnswers,
                'analytical_responses_preserved' => $responseIds->count(),
            ];
        });
    }

    protected function hasActiveHold(DataSubjectRequest $request): bool
    {
        return LegalHold::active()
            ->where('company_id', $request->company_id)
            ->where(function ($query) use ($request) {
                $query->whereNull('subject_user_id')
                    ->orWhere('subject_user_id', $request->subject_user_id);
            })
            ->exists();
    }

    protected function assertPrivacyOperator(User $user): void
    {
        if (! $user->hasCapability('privacy.manage')) {
            throw new \DomainException('A privacy operator capability is required.');
        }
    }

    protected function auditStatus(
        DataSubjectRequest $request,
        User $actor,
        string $status,
        array $metadata = []
    ): void {
        $this->audit->record(
            "privacy.request.{$status}",
            $actor,
            (int) $request->company_id,
            DataSubjectRequest::class,
            $request->id,
            ['status' => $status],
            $metadata
        );
    }
}
