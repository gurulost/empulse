<?php

namespace Tests\Feature;

use App\Jobs\SendSurveyAssignmentInvitation;
use App\Jobs\SendSurveyAssignmentReminder;
use App\Models\Companies;
use App\Models\EmailDeliveryEvent;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Services\DeliveryTrustService;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class DeliveryTrustTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_is_idempotent_and_provider_events_drive_honest_funnel(): void
    {
        $assignment = $this->assignment();
        $job = new SendSurveyAssignmentInvitation($assignment->id);
        $job->handle(app(EmailService::class));
        $job->handle(app(EmailService::class));

        $assignment->refresh();
        $this->assertSame('accepted', $assignment->invite_status);
        $this->assertDatabaseCount('email_delivery_events', 2);
        $this->assertDatabaseHas('email_delivery_events', [
            'survey_assignment_id' => $assignment->id,
            'status' => 'accepted',
            'provider_message_id' => 'testing-message',
        ]);

        config(['services.brevo.webhook_token' => str_repeat('w', 32)]);
        $this->withToken(str_repeat('w', 32))
            ->postJson(route('brevo.webhook'), [
                'id' => 'delivery-1',
                'event' => 'delivered',
                'message-id' => 'testing-message',
                'email' => $assignment->user->email,
            ])
            ->assertOk();

        $this->assertSame('delivered', $assignment->fresh()->invite_status);
        $this->assertDatabaseHas('email_delivery_events', [
            'survey_assignment_id' => $assignment->id,
            'status' => 'delivered',
        ]);
    }

    public function test_bounce_suppresses_future_mail_and_webhook_requires_bearer_auth(): void
    {
        $assignment = $this->assignment();
        (new SendSurveyAssignmentInvitation($assignment->id))->handle(app(EmailService::class));
        config(['services.brevo.webhook_token' => str_repeat('w', 32)]);

        $payload = [
            'id' => 'bounce-1',
            'event' => 'hardBounce',
            'message-id' => 'testing-message',
            'email' => $assignment->user->email,
        ];
        $this->postJson(route('brevo.webhook'), $payload)->assertUnauthorized();
        $this->withToken(str_repeat('w', 32))
            ->postJson(route('brevo.webhook'), $payload)
            ->assertOk();

        $this->assertSame('bounced', $assignment->fresh()->invite_status);
        $this->assertDatabaseHas('delivery_contacts', [
            'email' => $assignment->user->email,
            'status' => 'bounced',
            'suppression_reason' => 'bounced',
        ]);

        $second = SurveyAssignment::create([
            'survey_id' => $assignment->survey_id,
            'survey_version_id' => $assignment->survey_version_id,
            'survey_wave_id' => $assignment->survey_wave_id,
            'user_id' => $assignment->user_id,
            'status' => 'pending',
            'wave_label' => 'Second',
        ]);
        (new SendSurveyAssignmentInvitation($second->id))->handle(app(EmailService::class));

        $this->assertSame('suppressed', $second->fresh()->invite_status);
        $this->assertSame(3, EmailDeliveryEvent::count());
    }

    public function test_reminder_is_idempotent_and_respects_wave_closure(): void
    {
        $assignment = $this->assignment();
        $reminder = new SendSurveyAssignmentReminder($assignment->id, 1);
        $reminder->handle(app(EmailService::class), app(DeliveryTrustService::class));
        $reminder->handle(app(EmailService::class), app(DeliveryTrustService::class));

        $this->assertSame(1, $assignment->fresh()->reminder_count);
        $this->assertSame(
            1,
            EmailDeliveryEvent::where('message_kind', 'reminder')
                ->where('status', 'accepted')
                ->count()
        );

        $assignment->surveyWave->update(['status' => 'completed']);
        (new SendSurveyAssignmentReminder($assignment->id, 2))
            ->handle(app(EmailService::class), app(DeliveryTrustService::class));
        $this->assertSame(1, $assignment->fresh()->reminder_count);
    }

    public function test_failed_retry_reuses_the_same_token_url_and_provider_idempotency_key(): void
    {
        $assignment = $this->assignment();
        $email = new class extends EmailService
        {
            public array $urls = [];

            public array $keys = [];

            public int $calls = 0;

            public function sendSurveyInvitation(
                string $email,
                string $name,
                string $surveyUrl,
                string $companyName,
                ?string $waveLabel = null,
                ?string $providerIdempotencyKey = null
            ): array {
                $this->urls[] = $surveyUrl;
                $this->keys[] = $providerIdempotencyKey;
                $this->calls++;

                return $this->calls === 1
                    ? ['status' => 503, 'message' => 'temporary provider failure']
                    : ['status' => 202, 'provider_message_id' => 'retry-message'];
            }
        };
        $job = new SendSurveyAssignmentInvitation($assignment->id);

        $job->handle($email, app(DeliveryTrustService::class));
        $firstHash = $assignment->fresh()->token_hash;
        $job->handle($email, app(DeliveryTrustService::class));

        $this->assertSame($email->urls[0], $email->urls[1]);
        $this->assertSame($email->keys[0], $email->keys[1]);
        $this->assertTrue(Uuid::isValid($email->keys[0]));
        $this->assertSame($firstHash, $assignment->fresh()->token_hash);
        $this->assertSame('accepted', $assignment->fresh()->invite_status);
    }

    public function test_recent_in_flight_invitation_cannot_start_a_second_provider_attempt(): void
    {
        $assignment = $this->assignment();
        $delivery = app(DeliveryTrustService::class);
        $key = "assignment:{$assignment->id}:invitation:1";
        $urlFactory = fn (): string => 'https://example.test/survey/token';

        $first = $delivery->begin($assignment, 'invitation', $key, $urlFactory);
        $second = $delivery->begin($assignment->fresh(), 'invitation', $key, $urlFactory);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame('sending', $assignment->fresh()->invite_status);
        $this->assertSame(1, EmailDeliveryEvent::where('idempotency_key', $key)->count());

        SurveyAssignment::whereKey($assignment->id)->update([
            'updated_at' => now()->subMinutes(16),
        ]);
        $recovered = $delivery->begin($assignment->fresh(), 'invitation', $key, $urlFactory);
        $this->assertSame($first->id, $recovered?->id);
    }

    public function test_brevo_duplicate_response_is_treated_as_the_same_accepted_send(): void
    {
        config(['services.brevo.key' => 'test-key']);
        $providerKey = Uuid::uuid4()->toString();
        Http::fake([
            'api.brevo.com/*' => Http::response([
                'code' => 'duplicate_parameter',
                'message' => 'duplicate key',
            ], 400),
        ]);

        $response = app(EmailService::class)->sendLetter(
            'recipient@example.test',
            'Recipient',
            'Subject',
            '<p>Body</p>',
            $providerKey
        );

        $this->assertSame(202, $response['status']);
        $this->assertTrue($response['idempotent_replay']);
        Http::assertSent(function (Request $request) use ($providerKey): bool {
            return $request->data()['headers']['idempotencyKey'] === $providerKey;
        });
    }

    public function test_automatic_retry_stops_before_provider_idempotency_expires(): void
    {
        Carbon::setTestNow(now()->startOfSecond());
        $assignment = $this->assignment();
        $email = new class extends EmailService
        {
            public int $calls = 0;

            public function sendSurveyInvitation(
                string $email,
                string $name,
                string $surveyUrl,
                string $companyName,
                ?string $waveLabel = null,
                ?string $providerIdempotencyKey = null
            ): array {
                $this->calls++;

                return ['status' => 503, 'message' => 'temporary provider failure'];
            }
        };
        $job = new SendSurveyAssignmentInvitation($assignment->id);
        $job->handle($email, app(DeliveryTrustService::class));

        Carbon::setTestNow(now()->addMinutes(26));
        $job->handle($email, app(DeliveryTrustService::class));

        $this->assertSame(1, $email->calls);
        $this->assertSame('manual_review', $assignment->fresh()->invite_status);
        $this->assertStringContainsString(
            'verify provider activity',
            (string) $assignment->fresh()->invite_error
        );
        Carbon::setTestNow();
    }

    private function assignment(): SurveyAssignment
    {
        $company = Companies::create([
            'title' => 'Delivery Co',
            'manager' => 'Manager',
            'manager_email' => 'manager@delivery.test',
        ]);
        $user = User::factory()->create([
            'name' => 'Employee',
            'email' => 'employee@delivery.test',
            'company_id' => $company->id,
            'company_title' => $company->title,
            'role' => 4,
            'status' => 'active',
        ]);
        $survey = Survey::create([
            'company_id' => $company->id,
            'title' => 'Survey',
            'is_default' => true,
        ]);
        $version = SurveyVersion::create([
            'instrument_id' => 'delivery',
            'version' => '1.0.0',
            'title' => 'Survey',
            'is_active' => true,
        ]);
        $wave = SurveyWave::create([
            'company_id' => $company->id,
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'kind' => 'full',
            'label' => 'Baseline',
            'status' => 'active',
            'cadence' => 'manual',
        ]);

        return SurveyAssignment::create([
            'survey_id' => $survey->id,
            'survey_version_id' => $version->id,
            'survey_wave_id' => $wave->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'wave_label' => $wave->label,
            'dispatch_count' => 1,
        ]);
    }
}
