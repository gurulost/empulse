<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class VerifyDeployment extends Command
{
    protected $signature = 'app:verify-deployment
        {base_url : Canonical HTTPS origin to verify}
        {expected_sha : Exact lowercase 40-character Git SHA expected to be served}
        {expected_environment : Exact DEPLOYMENT_ENVIRONMENT_ID expected to be served}
        {--json : Emit the complete machine-readable report}';

    protected $description = 'Verify deployed identity, readiness, process freshness, and security headers without claiming full production sign-off.';

    public function handle(): int
    {
        $baseUrl = rtrim((string) $this->argument('base_url'), '/');
        $expectedSha = (string) $this->argument('expected_sha');
        $expectedEnvironment = trim((string) $this->argument('expected_environment'));
        $checks = [];

        $this->record(
            $checks,
            'base_url_https_origin',
            $this->isHttpsOrigin($baseUrl),
            $baseUrl
        );
        $this->record(
            $checks,
            'expected_sha_format',
            preg_match('/\A[0-9a-f]{40}\z/', $expectedSha) === 1,
            $expectedSha
        );
        $this->record(
            $checks,
            'expected_environment_present',
            $expectedEnvironment !== '',
            $expectedEnvironment
        );

        if ($this->hasFailure($checks)) {
            return $this->finish($baseUrl, $expectedSha, $expectedEnvironment, $checks);
        }

        try {
            $client = Http::acceptJson()
                ->timeout(15)
                ->connectTimeout(5)
                ->withoutRedirecting();

            $liveness = $client->get($baseUrl.'/api/healthz');
            $readiness = $client->get($baseUrl.'/api/readyz');
            $login = $client->get($baseUrl.'/login');

            $this->recordHealthChecks(
                $checks,
                'liveness',
                $liveness,
                'live',
                $expectedSha,
                $expectedEnvironment
            );
            $this->recordHealthChecks(
                $checks,
                'readiness',
                $readiness,
                'ready',
                $expectedSha,
                $expectedEnvironment
            );

            $readinessChecks = $readiness->json('checks');
            foreach ([
                'database' => 'connected',
                'runtime_tables' => 'available',
                'scheduler' => 'fresh',
                'worker' => 'fresh',
            ] as $name => $expected) {
                $actual = is_array($readinessChecks) ? ($readinessChecks[$name] ?? null) : null;
                $this->record(
                    $checks,
                    'readiness_'.$name,
                    $actual === $expected,
                    is_scalar($actual) ? (string) $actual : 'missing'
                );
            }

            $this->record(
                $checks,
                'login_http_status',
                $login->successful(),
                (string) $login->status()
            );
            $this->record(
                $checks,
                'login_release_header',
                hash_equals($expectedSha, (string) $login->header('X-Empulse-Release')),
                (string) $login->header('X-Empulse-Release')
            );

            foreach ([
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
            ] as $header => $expected) {
                $this->record(
                    $checks,
                    'security_header_'.strtolower(str_replace('-', '_', $header)),
                    $login->header($header) === $expected,
                    (string) $login->header($header)
                );
            }

            $contentSecurityPolicy = (string) $login->header('Content-Security-Policy');
            $this->record(
                $checks,
                'security_header_content_security_policy',
                $contentSecurityPolicy !== ''
                    && str_contains($contentSecurityPolicy, "default-src 'self'")
                    && str_contains($contentSecurityPolicy, "frame-ancestors 'none'"),
                $contentSecurityPolicy
            );
        } catch (Throwable $exception) {
            $this->record(
                $checks,
                'http_connectivity',
                false,
                $exception::class
            );
        }

        return $this->finish($baseUrl, $expectedSha, $expectedEnvironment, $checks);
    }

    /**
     * @param  array<int, array{name: string, status: string, observed: string}>  $checks
     */
    protected function recordHealthChecks(
        array &$checks,
        string $surface,
        Response $response,
        string $expectedStatus,
        string $expectedSha,
        string $expectedEnvironment
    ): void {
        $this->record(
            $checks,
            $surface.'_http_status',
            $response->successful(),
            (string) $response->status()
        );
        $this->record(
            $checks,
            $surface.'_application_status',
            $response->json('status') === $expectedStatus,
            (string) $response->json('status')
        );
        $this->record(
            $checks,
            $surface.'_release_identity',
            hash_equals($expectedSha, (string) $response->json('release_sha'))
                && hash_equals($expectedSha, (string) $response->header('X-Empulse-Release')),
            (string) $response->json('release_sha')
        );
        $this->record(
            $checks,
            $surface.'_environment_identity',
            hash_equals($expectedEnvironment, (string) $response->json('environment_id')),
            (string) $response->json('environment_id')
        );
    }

    /**
     * @param  array<int, array{name: string, status: string, observed: string}>  $checks
     */
    protected function record(array &$checks, string $name, bool $passed, string $observed): void
    {
        $checks[] = [
            'name' => $name,
            'status' => $passed ? 'passed' : 'failed',
            'observed' => $observed,
        ];
    }

    /**
     * @param  array<int, array{name: string, status: string, observed: string}>  $checks
     */
    protected function hasFailure(array $checks): bool
    {
        return collect($checks)->contains(
            fn (array $check): bool => $check['status'] === 'failed'
        );
    }

    protected function isHttpsOrigin(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return false;
        }

        $path = array_key_exists('path', $parts) ? $parts['path'] : '';

        return ($parts['scheme'] ?? null) === 'https'
            && isset($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && ($path === '' || $path === '/');
    }

    /**
     * @param  array<int, array{name: string, status: string, observed: string}>  $checks
     */
    protected function finish(
        string $baseUrl,
        string $expectedSha,
        string $expectedEnvironment,
        array $checks
    ): int {
        $passed = ! $this->hasFailure($checks);
        $report = [
            'schema_version' => 1,
            'checked_at' => now()->toIso8601String(),
            'target' => [
                'base_url' => $baseUrl,
                'expected_release_sha' => $expectedSha,
                'expected_environment_id' => $expectedEnvironment,
            ],
            'passed' => $passed,
            'checks' => $checks,
            'production_signoff' => false,
            'boundary' => 'This verifies served identity, health, process freshness, and response headers only. It does not prove mail, Stripe, load, backup, rollback, alerts, accessibility, privacy, methodology, legal, or commercial approval.',
        ];

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif ($passed) {
            $this->info('Deployment identity and runtime surface verification passed.');
            $this->line('Production sign-off remains false; complete the rest of the release packet.');
        } else {
            $this->error('Deployment identity and runtime surface verification failed.');
            foreach ($checks as $check) {
                if ($check['status'] === 'failed') {
                    $this->line(" - {$check['name']}: observed {$check['observed']}");
                }
            }
        }

        return $passed ? self::SUCCESS : self::FAILURE;
    }
}
