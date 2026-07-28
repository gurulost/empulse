<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_forged_host_and_forwarded_host_cannot_change_generated_urls(): void
    {
        $host = 'attacker.example';

        $response = $this->withServerVariables([
            'HTTP_HOST' => $host,
            'HTTP_X_FORWARDED_HOST' => $host,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get("http://{$host}/login");

        $canonicalUrl = rtrim((string) config('app.url'), '/');

        $response->assertOk();
        $response->assertSee("action=\"{$canonicalUrl}/login\"", false);
        $response->assertSee("href=\"{$canonicalUrl}/register\"", false);
        $response->assertDontSee($host);
    }

    public function test_secure_responses_include_the_production_security_header_baseline(): void
    {
        $response = $this->get('https://localhost/login');

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=(), payment=(self)');
        $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString(
            "frame-ancestors 'none'",
            $contentSecurityPolicy
        );
        $this->assertStringContainsString("font-src 'self' data:", $contentSecurityPolicy);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $contentSecurityPolicy);
        $this->assertStringNotContainsString('fonts.googleapis.com', $contentSecurityPolicy);
        $this->assertStringNotContainsString('fonts.gstatic.com', $contentSecurityPolicy);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $contentSecurityPolicy);
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $contentSecurityPolicy);
    }
}
