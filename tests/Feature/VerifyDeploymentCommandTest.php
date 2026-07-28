<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\CreatesApplication;

class VerifyDeploymentCommandTest extends TestCase
{
    use CreatesApplication;

    public function test_deployment_verifier_requires_matching_identity_readiness_and_headers(): void
    {
        $sha = str_repeat('a', 40);
        $environment = 'empulse-staging-us-east';
        $identityHeaders = ['X-Empulse-Release' => $sha];
        $securityHeaders = [
            ...$identityHeaders,
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; frame-ancestors 'none'",
        ];

        Http::fake([
            'https://staging.empulse.example/api/healthz' => Http::response([
                'status' => 'live',
                'release_sha' => $sha,
                'environment_id' => $environment,
            ], 200, $identityHeaders),
            'https://staging.empulse.example/api/readyz' => Http::response([
                'status' => 'ready',
                'release_sha' => $sha,
                'environment_id' => $environment,
                'checks' => [
                    'database' => 'connected',
                    'runtime_tables' => 'available',
                    'scheduler' => 'fresh',
                    'worker' => 'fresh',
                ],
            ], 200, $identityHeaders),
            'https://staging.empulse.example/login' => Http::response(
                '<html></html>',
                200,
                $securityHeaders
            ),
        ]);

        $this->artisan('app:verify-deployment', [
            'base_url' => 'https://staging.empulse.example',
            'expected_sha' => $sha,
            'expected_environment' => $environment,
        ])
            ->expectsOutput('Deployment identity and runtime surface verification passed.')
            ->expectsOutput('Production sign-off remains false; complete the rest of the release packet.')
            ->assertSuccessful();

        $exitCode = Artisan::call('app:verify-deployment', [
            'base_url' => 'https://staging.empulse.example',
            'expected_sha' => $sha,
            'expected_environment' => $environment,
            '--json' => true,
        ]);

        $output = Artisan::output();
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"passed": true', $output);
        $this->assertStringContainsString('"production_signoff": false', $output);
        $this->assertStringContainsString(
            'It does not prove mail, Stripe, load, backup, rollback, alerts',
            $output
        );
    }

    public function test_deployment_verifier_fails_for_stale_process_or_wrong_release(): void
    {
        $expectedSha = str_repeat('a', 40);
        $servedSha = str_repeat('b', 40);
        $environment = 'empulse-staging-us-east';

        Http::fake([
            'https://staging.empulse.example/api/healthz' => Http::response([
                'status' => 'live',
                'release_sha' => $servedSha,
                'environment_id' => $environment,
            ], 200, ['X-Empulse-Release' => $servedSha]),
            'https://staging.empulse.example/api/readyz' => Http::response([
                'status' => 'not_ready',
                'release_sha' => $servedSha,
                'environment_id' => $environment,
                'checks' => [
                    'database' => 'connected',
                    'runtime_tables' => 'available',
                    'scheduler' => 'fresh',
                    'worker' => 'stale',
                ],
            ], 503, ['X-Empulse-Release' => $servedSha]),
            'https://staging.empulse.example/login' => Http::response('', 200),
        ]);

        $this->artisan('app:verify-deployment', [
            'base_url' => 'https://staging.empulse.example',
            'expected_sha' => $expectedSha,
            'expected_environment' => $environment,
        ])
            ->expectsOutput('Deployment identity and runtime surface verification failed.')
            ->expectsOutputToContain('liveness_release_identity')
            ->expectsOutputToContain('readiness_worker')
            ->assertFailed();
    }

    public function test_deployment_verifier_rejects_non_https_or_non_commit_inputs_before_network(): void
    {
        Http::fake();

        $this->artisan('app:verify-deployment', [
            'base_url' => 'http://staging.empulse.example/path',
            'expected_sha' => 'main',
            'expected_environment' => '',
        ])
            ->expectsOutput('Deployment identity and runtime surface verification failed.')
            ->expectsOutputToContain('base_url_https_origin')
            ->expectsOutputToContain('expected_sha_format')
            ->expectsOutputToContain('expected_environment_present')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
