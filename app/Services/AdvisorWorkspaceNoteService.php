<?php

namespace App\Services;

use App\Models\AdvisorWorkspaceNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AdvisorWorkspaceNoteService
{
    public function __construct(
        protected AdvisorAccessService $access,
        protected AuditTrailService $audit
    ) {}

    public function visibleTo(User $actor, int $companyId): Collection
    {
        $this->access->assertActorCanAccess($actor, $companyId);
        $isAdvisor = $this->access->canAdvise($actor, $companyId);

        return AdvisorWorkspaceNote::query()
            ->with('author:id,name')
            ->where('company_id', $companyId)
            ->when(
                ! $isAdvisor,
                fn ($query) => $query->where('visibility', 'customer_shared')
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function create(
        User $actor,
        int $companyId,
        string $visibility,
        string $body
    ): AdvisorWorkspaceNote {
        $this->access->assertActorCanAccess($actor, $companyId);
        if (! $actor->hasCapability('actions.manage')) {
            throw new \DomainException('Action-management access is required to add a workspace note.');
        }

        $isAdvisor = $this->access->canAdvise($actor, $companyId);
        if ($visibility === 'workfit_internal' && ! $isAdvisor) {
            throw new \DomainException('Only a customer-approved WorkFit advisor can create an internal note.');
        }
        if (! in_array($visibility, ['customer_shared', 'workfit_internal'], true)) {
            throw new \DomainException('The note visibility is not supported.');
        }

        $grant = $isAdvisor
            ? $this->access->activeGrantForAdvisor($actor, $companyId)
            : null;
        $note = AdvisorWorkspaceNote::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'advisor_company_grant_id' => $grant?->id,
            'author_user_id' => $actor->id,
            'visibility' => $visibility,
            'body' => trim($body),
            'created_at' => now(),
        ]);

        $this->audit->record(
            'advisor_note.created',
            $actor,
            $companyId,
            $note::class,
            $note->id,
            [],
            [
                'visibility' => $visibility,
                'body_length' => mb_strlen($note->body),
                'advisor_grant_id' => $grant?->id,
            ]
        );

        return $note;
    }
}
