<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPageSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_form_respects_forwarded_https_scheme(): void
    {
        $host = 'secure.empulse.test';

        $response = $this->withServerVariables([
            'HTTP_HOST' => $host,
            'HTTP_X_FORWARDED_HOST' => $host,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get("http://{$host}/login");

        $response->assertOk();
        $response->assertSee("action=\"https://{$host}/login\"", false);
        $response->assertDontSee("action=\"http://{$host}/login\"", false);
        $response->assertDontSee("href=\"http://{$host}/register\"", false);
    }
}
