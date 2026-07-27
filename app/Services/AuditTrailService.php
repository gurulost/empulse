<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuditTrailService
{
    public function record(
        string $action,
        ?User $actor = null,
        ?int $companyId = null,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $changes = [],
        array $metadata = []
    ): AuditEvent {
        return DB::transaction(function () use (
            $action,
            $actor,
            $companyId,
            $subjectType,
            $subjectId,
            $changes,
            $metadata
        ) {
            $streamKey = $companyId ? "company:{$companyId}" : 'platform';
            $previous = AuditEvent::query()
                ->where('stream_key', $streamKey)
                ->orderByDesc('sequence')
                ->lockForUpdate()
                ->first();
            $occurredAt = now()->startOfSecond();
            $payload = [
                'stream_key' => $streamKey,
                'sequence' => ((int) ($previous ? $previous->sequence : 0)) + 1,
                'company_id' => $companyId,
                'actor_user_id' => $actor?->id,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId === null ? null : (string) $subjectId,
                'changes' => $changes ?: null,
                'metadata' => $metadata ?: null,
                'previous_hash' => $previous?->event_hash,
                'occurred_at' => $occurredAt->toISOString(),
            ];
            $eventHash = hash_hmac(
                'sha256',
                $this->canonicalJson($payload),
                $this->hashKey()
            );

            return AuditEvent::create([
                ...$payload,
                'event_hash' => $eventHash,
                'occurred_at' => $occurredAt,
            ]);
        }, 3);
    }

    public function verify(?int $companyId = null): array
    {
        $streamKey = $companyId ? "company:{$companyId}" : 'platform';
        $previousHash = null;
        $expectedSequence = 1;

        foreach (AuditEvent::where('stream_key', $streamKey)->orderBy('sequence')->cursor() as $event) {
            $payload = [
                'stream_key' => $event->stream_key,
                'sequence' => $event->sequence,
                'company_id' => $event->company_id,
                'actor_user_id' => $event->actor_user_id,
                'action' => $event->action,
                'subject_type' => $event->subject_type,
                'subject_id' => $event->subject_id,
                'changes' => $event->changes,
                'metadata' => $event->metadata,
                'previous_hash' => $event->previous_hash,
                'occurred_at' => $event->occurred_at->toISOString(),
            ];
            $expectedHash = hash_hmac('sha256', $this->canonicalJson($payload), $this->hashKey());
            if ($event->sequence !== $expectedSequence
                || $event->previous_hash !== $previousHash
                || ! hash_equals($expectedHash, $event->event_hash)) {
                return [
                    'valid' => false,
                    'stream_key' => $streamKey,
                    'failed_event_id' => $event->id,
                ];
            }

            $previousHash = $event->event_hash;
            $expectedSequence++;
        }

        return [
            'valid' => true,
            'stream_key' => $streamKey,
            'events' => $expectedSequence - 1,
            'head_hash' => $previousHash,
        ];
    }

    protected function canonicalJson(array $payload): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (! is_array($value)) {
                return $value;
            }

            if (array_is_list($value)) {
                return array_map($normalize, $value);
            }

            ksort($value);

            return array_map($normalize, $value);
        };

        return json_encode(
            $normalize($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    protected function hashKey(): string
    {
        return (string) config('runtime.audit_hash_key', config('app.key'));
    }
}
