<?php

namespace App\Services;

use App\Models\AccountInvitation;
use App\Models\EmailDeliveryEvent;
use App\Models\LegalHold;
use App\Models\RetentionRun;
use App\Models\SurveyAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetentionService
{
    public function __construct(protected AuditTrailService $audit) {}

    public function plan(): array
    {
        $heldCompanies = LegalHold::active()->pluck('company_id')->unique()->all();
        $draftCutoff = now()->startOfDay()->subDays((int) config('privacy.retention_days.expired_drafts', 30));
        $invitationCutoff = now()->startOfDay()->subDays((int) config('privacy.retention_days.expired_invitations', 30));
        $deliveryCutoff = now()->startOfDay()->subDays((int) config('privacy.retention_days.delivery_events', 400));
        $onboardingCutoff = now()->startOfDay()->subDays((int) config('privacy.retention_days.onboarding_events', 400));

        $draftIds = SurveyAssignment::query()
            ->from('survey_assignments as sa')
            ->join('users as u', 'u.id', '=', 'sa.user_id')
            ->whereNotNull('sa.draft_answers')
            ->where(function ($query) use ($draftCutoff) {
                $query->where('sa.updated_at', '<', $draftCutoff)
                    ->orWhereNotNull('sa.completed_at')
                    ->orWhereNotNull('sa.token_revoked_at');
            })
            ->when($heldCompanies !== [], fn ($query) => $query->whereNotIn('u.company_id', $heldCompanies))
            ->limit(10000)
            ->pluck('sa.id')
            ->all();

        $invitationIds = AccountInvitation::query()
            ->where(function ($query) use ($invitationCutoff) {
                $query->where('expires_at', '<', $invitationCutoff)
                    ->orWhere('accepted_at', '<', $invitationCutoff)
                    ->orWhere('revoked_at', '<', $invitationCutoff);
            })
            ->when($heldCompanies !== [], fn ($query) => $query->whereNotIn('company_id', $heldCompanies))
            ->limit(10000)
            ->pluck('id')
            ->all();

        $deliveryIds = EmailDeliveryEvent::query()
            ->where('occurred_at', '<', $deliveryCutoff)
            ->when($heldCompanies !== [], fn ($query) => $query->whereNotIn('company_id', $heldCompanies))
            ->limit(10000)
            ->pluck('id')
            ->all();

        $onboardingIds = DB::table('onboarding_events')
            ->where('created_at', '<', $onboardingCutoff)
            ->when($heldCompanies !== [], fn ($query) => $query->whereNotIn('company_id', $heldCompanies))
            ->limit(10000)
            ->pluck('id')
            ->all();

        return [
            'schema_version' => 1,
            'cutoffs' => [
                'drafts' => $draftCutoff->toIso8601String(),
                'invitations' => $invitationCutoff->toIso8601String(),
                'delivery_events' => $deliveryCutoff->toIso8601String(),
                'onboarding_events' => $onboardingCutoff->toIso8601String(),
            ],
            'held_company_ids' => array_values($heldCompanies),
            'targets' => [
                'draft_assignment_ids' => $draftIds,
                'account_invitation_ids' => $invitationIds,
                'email_delivery_event_ids' => $deliveryIds,
                'onboarding_event_ids' => $onboardingIds,
            ],
        ];
    }

    public function hash(array $plan): string
    {
        return hash('sha256', json_encode($plan, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function recordPlan(array $plan, bool $dryRun, ?int $actorId = null): RetentionRun
    {
        return RetentionRun::create([
            'public_id' => (string) Str::uuid(),
            'initiated_by_user_id' => $actorId,
            'dry_run' => $dryRun,
            'status' => 'planned',
            'plan_hash' => $this->hash($plan),
            'plan' => $plan,
        ]);
    }

    public function execute(RetentionRun $run, string $confirmationHash): array
    {
        if ($run->dry_run) {
            throw new \DomainException('A dry-run record cannot execute deletions.');
        }
        if (! hash_equals($run->plan_hash, $confirmationHash)) {
            throw new \DomainException('The retention plan confirmation hash does not match.');
        }
        if ($run->status !== 'planned') {
            throw new \DomainException('This retention plan is no longer executable.');
        }

        $targets = $run->plan['targets'] ?? [];
        $result = DB::transaction(function () use ($targets) {
            $drafts = SurveyAssignment::whereIn('id', $targets['draft_assignment_ids'] ?? [])
                ->update([
                    'draft_answers' => null,
                    'last_autosaved_at' => null,
                    'draft_revision' => 0,
                ]);
            $invitations = AccountInvitation::whereIn('id', $targets['account_invitation_ids'] ?? [])->delete();
            $delivery = DB::table('email_delivery_events')
                ->whereIn('id', $targets['email_delivery_event_ids'] ?? [])
                ->delete();
            $onboarding = DB::table('onboarding_events')
                ->whereIn('id', $targets['onboarding_event_ids'] ?? [])
                ->delete();

            return compact('drafts', 'invitations', 'delivery', 'onboarding');
        });

        $run->update([
            'status' => 'completed',
            'result' => $result,
            'executed_at' => now(),
        ]);
        $this->audit->record(
            'privacy.retention.executed',
            null,
            null,
            RetentionRun::class,
            $run->id,
            $result,
            ['plan_hash' => $run->plan_hash]
        );

        return $result;
    }
}
