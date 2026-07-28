<?php

namespace App\Services;

use App\Models\DeliveryContact;
use App\Models\EmailDeliveryEvent;
use App\Models\SurveyAssignment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DeliveryTrustService
{
    public function contactFor(SurveyAssignment $assignment): DeliveryContact
    {
        $assignment->loadMissing('user');

        return DeliveryContact::updateOrCreate(
            [
                'company_id' => (int) $assignment->user->company_id,
                'email' => mb_strtolower(trim($assignment->user->email)),
            ],
            [
                'user_id' => $assignment->user_id,
            ]
        );
    }

    public function begin(
        SurveyAssignment $assignment,
        string $messageKind,
        string $idempotencyKey,
        ?callable $surveyUrlFactory = null
    ): ?EmailDeliveryEvent {
        $contact = $this->contactFor($assignment);
        if ($contact->suppressed_at || $contact->status !== 'deliverable') {
            return null;
        }

        return DB::transaction(function () use (
            $assignment,
            $messageKind,
            $idempotencyKey,
            $surveyUrlFactory,
            $contact
        ): ?EmailDeliveryEvent {
            $lockedAssignment = SurveyAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($messageKind === 'invitation'
                && $lockedAssignment->invite_status === 'sending'
                && $lockedAssignment->updated_at
                && $lockedAssignment->updated_at->greaterThan(now()->subMinutes(15))) {
                return null;
            }

            $existing = EmailDeliveryEvent::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                $terminalExists = EmailDeliveryEvent::query()
                    ->where('idempotency_key', 'like', $idempotencyKey.':%')
                    ->whereIn('status', ['accepted', 'delivered', 'bounced', 'complained', 'unsubscribed'])
                    ->exists();
                if ($terminalExists || $this->retryWindowExpired($existing)) {
                    return null;
                }

                $attempt = in_array($existing->status, ['queued', 'failed'], true)
                    ? $existing
                    : null;
            } else {
                $metadata = [
                    'provider_idempotency_key' => Uuid::uuid5(
                        Uuid::NAMESPACE_URL,
                        'https://empulse.workfitdx.com/email/'.$idempotencyKey
                    )->toString(),
                    'automatic_retry_until' => now()
                        ->addMinutes((int) config('services.brevo.idempotency_retry_minutes', 25))
                        ->toISOString(),
                ];
                if ($surveyUrlFactory) {
                    $metadata['survey_url_ciphertext'] = Crypt::encryptString((string) $surveyUrlFactory());
                }

                $attempt = EmailDeliveryEvent::create([
                    'delivery_contact_id' => $contact->id,
                    'survey_assignment_id' => $assignment->id,
                    'idempotency_key' => $idempotencyKey,
                    'message_kind' => $messageKind,
                    'status' => 'queued',
                    'occurred_at' => now(),
                    'metadata' => $metadata,
                ]);
            }

            if ($attempt && $messageKind === 'invitation') {
                $lockedAssignment->update([
                    'invite_status' => 'sending',
                    'invite_error' => null,
                ]);
            }

            return $attempt;
        }, 3);
    }

    public function surveyUrl(EmailDeliveryEvent $attempt): string
    {
        $ciphertext = $attempt->metadata['survey_url_ciphertext'] ?? null;
        if (! is_string($ciphertext) || $ciphertext === '') {
            throw new \DomainException('The delivery attempt has no reusable survey URL.');
        }

        return Crypt::decryptString($ciphertext);
    }

    public function providerIdempotencyKey(EmailDeliveryEvent $attempt): string
    {
        $key = $attempt->metadata['provider_idempotency_key'] ?? null;
        if (! is_string($key) || ! Uuid::isValid($key)) {
            throw new \DomainException('The delivery attempt has no valid provider idempotency key.');
        }

        return $key;
    }

    public function retryWindowExpired(EmailDeliveryEvent $attempt): bool
    {
        $until = $attempt->metadata['automatic_retry_until'] ?? null;

        return ! is_string($until) || now()->greaterThanOrEqualTo($until);
    }

    public function record(
        EmailDeliveryEvent $attempt,
        string $status,
        ?string $providerMessageId = null,
        array $metadata = []
    ): EmailDeliveryEvent {
        return EmailDeliveryEvent::create([
            'delivery_contact_id' => $attempt->delivery_contact_id,
            'survey_assignment_id' => $attempt->survey_assignment_id,
            'idempotency_key' => "{$attempt->idempotency_key}:{$status}:".now()->format('Uu'),
            'message_kind' => $attempt->message_kind,
            'status' => $status,
            'provider' => $attempt->provider ?: 'brevo',
            'provider_message_id' => $providerMessageId ?: $attempt->provider_message_id,
            'occurred_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    public function ingestProviderEvent(array $payload): ?EmailDeliveryEvent
    {
        $messageId = (string) ($payload['message-id'] ?? $payload['messageId'] ?? '');
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $eventName = mb_strtolower((string) ($payload['event'] ?? ''));
        $status = match ($eventName) {
            'delivered' => 'delivered',
            'hardbounce', 'softbounce', 'blocked', 'invalid' => 'bounced',
            'spam', 'complaint' => 'complained',
            'unsubscribed' => 'unsubscribed',
            default => null,
        };
        if (! $status) {
            return null;
        }

        $prior = EmailDeliveryEvent::query()
            ->when($messageId !== '', fn ($query) => $query->where('provider_message_id', $messageId))
            ->orderByDesc('id')
            ->first();
        $contact = $prior
            ? DeliveryContact::find($prior->delivery_contact_id)
            : DeliveryContact::where('email', $email)->orderByDesc('id')->first();
        if (! $contact) {
            return null;
        }

        return DB::transaction(function () use ($payload, $messageId, $status, $contact, $prior) {
            $eventKey = (string) ($payload['id'] ?? hash('sha256', json_encode($payload)));
            $event = EmailDeliveryEvent::firstOrCreate(
                ['idempotency_key' => "brevo:{$eventKey}"],
                [
                    'delivery_contact_id' => $contact->id,
                    'survey_assignment_id' => $prior?->survey_assignment_id,
                    'message_kind' => $prior?->message_kind ?? 'unknown',
                    'status' => $status,
                    'provider' => 'brevo',
                    'provider_message_id' => $messageId ?: null,
                    'occurred_at' => now(),
                    'metadata' => ['provider_event' => $payload['event'] ?? null],
                ]
            );

            if (in_array($status, ['bounced', 'complained', 'unsubscribed'], true)) {
                $contact->update([
                    'status' => $status,
                    'failure_count' => $contact->failure_count + 1,
                    'suppressed_at' => now(),
                    'suppression_reason' => $status,
                ]);
            }

            if ($event->survey_assignment_id) {
                SurveyAssignment::whereKey($event->survey_assignment_id)->update([
                    'invite_status' => $status,
                    'invite_error' => in_array($status, ['bounced', 'complained', 'unsubscribed'], true)
                        ? "Delivery {$status}; address suppressed."
                        : null,
                ]);
            }

            return $event;
        });
    }
}
