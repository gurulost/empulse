<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_validates_and_delivers_the_customer_message_without_html_injection(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'contact-message'], 201),
        ]);

        $this->post(route('contact.send'), [
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'phone' => '',
            'message' => 'Please help with <script>alert("unsafe")</script> onboarding.',
            'website' => '',
        ])->assertRedirect(route('contact.response'));

        Http::assertSent(function ($request): bool {
            $content = (string) $request['htmlContent'];

            return str_contains($content, 'Please help with')
                && str_contains($content, '&lt;script&gt;')
                && ! str_contains($content, '<script>');
        });
    }

    public function test_contact_form_rejects_invalid_input_and_hides_provider_failures(): void
    {
        $this->from(route('contact.form'))
            ->post(route('contact.send'), [
                'name' => 'A',
                'email' => 'not-email',
                'message' => 'short',
            ])
            ->assertRedirect(route('contact.form'))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        config(['services.brevo.key' => 'test-key']);
        Http::fake([
            'api.brevo.com/*' => Http::response(
                'provider-secret-body with SQLSTATE and token=do-not-expose',
                500
            ),
        ]);
        $response = $this->from(route('contact.form'))
            ->post(route('contact.send'), [
                'name' => 'Customer',
                'email' => 'customer@example.test',
                'message' => 'This is a valid support request.',
                'website' => '',
            ])
            ->assertRedirect(route('contact.form'))
            ->assertSessionHasErrors();

        $sessionErrors = json_encode(
            $response->getSession()->get('errors')->all(),
            JSON_THROW_ON_ERROR
        );
        $this->assertStringNotContainsString('provider-secret-body', $sessionErrors);
        $this->assertStringNotContainsString('SQLSTATE', $sessionErrors);
    }

    public function test_contact_form_honeypot_drops_automated_submission_without_sending(): void
    {
        config(['services.brevo.key' => 'test-key']);
        Http::fake();

        $this->post(route('contact.send'), [
            'name' => 'Bot Customer',
            'email' => 'bot@example.test',
            'message' => 'This automated message should never be sent.',
            'website' => 'https://spam.example',
        ])->assertRedirect(route('contact.response'));

        Http::assertNothingSent();
    }
}
