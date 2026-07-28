<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\TestCase;
use Tests\CreatesApplication;

class ProductionConfigurationTest extends TestCase
{
    use CreatesApplication;

    public function test_production_check_passes_for_the_required_runtime_contract(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:production-key',
            'runtime.audit_hash_key' => str_repeat('a', 32),
            'runtime.release_sha' => str_repeat('b', 40),
            'runtime.deployment_environment_id' => 'empulse-production-us-east',
            'runtime.require_process_heartbeats' => true,
            'runtime.heartbeat_max_age_seconds' => 180,
            'app.url' => 'https://empulse.example.com',
            'database.default' => 'pgsql',
            'queue.default' => 'database',
            'session.driver' => 'database',
            'session.secure' => true,
            'cache.default' => 'database',
            'filesystems.avatar_disk' => 'public',
            'filesystems.disks.public' => ['driver' => 'local'],
            'mail.default' => 'smtp',
            'mail.from.address' => 'hello@example.com',
            'services.brevo.key' => 'brevo-key',
            'services.brevo.webhook_token' => str_repeat('b', 32),
            'services.stripe.key' => 'stripe-key',
            'services.stripe.secret' => 'stripe-secret',
            'services.stripe.webhook_secret' => 'webhook-secret',
            'billing.catalog.starter.stripe_price' => 'price_starter',
            'billing.catalog.pulse.stripe_price' => 'price_pulse',
            'billing.catalog.starter.price_cents' => 9900,
            'billing.catalog.pulse.price_cents' => 19900,
        ]);

        $this->artisan('app:production-check', ['--skip-connectivity' => true])
            ->expectsOutput('Production configuration check passed.')
            ->assertSuccessful();
    }

    public function test_production_check_reports_unsafe_configuration(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => null,
            'runtime.audit_hash_key' => null,
            'runtime.release_sha' => 'main',
            'runtime.deployment_environment_id' => null,
            'runtime.require_process_heartbeats' => false,
            'runtime.heartbeat_max_age_seconds' => 3600,
            'app.url' => 'http://empulse.example.com',
            'database.default' => 'sqlite',
            'queue.default' => 'sync',
            'session.driver' => 'file',
            'session.secure' => false,
            'cache.default' => 'file',
            'filesystems.avatar_disk' => 'missing-avatar-disk',
            'filesystems.disks' => [],
            'mail.default' => 'log',
            'mail.from.address' => null,
            'services.brevo.key' => null,
            'services.brevo.webhook_token' => null,
            'services.stripe.key' => null,
            'services.stripe.secret' => null,
            'services.stripe.webhook_secret' => null,
            'billing.catalog.starter.stripe_price' => null,
            'billing.catalog.pulse.stripe_price' => null,
            'billing.catalog.starter.price_cents' => null,
            'billing.catalog.pulse.price_cents' => null,
        ]);

        $this->artisan('app:production-check', ['--skip-connectivity' => true])
            ->expectsOutputToContain('APP_DEBUG')
            ->expectsOutputToContain('DB_CONNECTION')
            ->expectsOutputToContain('AUDIT_HASH_KEY')
            ->expectsOutputToContain('APP_RELEASE_SHA')
            ->expectsOutputToContain('DEPLOYMENT_ENVIRONMENT_ID')
            ->expectsOutputToContain('REQUIRE_PROCESS_HEARTBEATS')
            ->expectsOutputToContain('HEARTBEAT_MAX_AGE_SECONDS')
            ->expectsOutputToContain('AVATAR_DISK')
            ->expectsOutputToContain('STRIPE_WEBHOOK_SECRET')
            ->assertFailed();
    }
}
