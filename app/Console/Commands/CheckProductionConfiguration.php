<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckProductionConfiguration extends Command
{
    protected $signature = 'app:production-check
        {--skip-connectivity : Validate configuration without opening a database connection}';

    protected $description = 'Fail closed when required Empulse production configuration is missing or unsafe.';

    public function handle(): int
    {
        $failures = array_values(array_filter([
            $this->mustEqual('APP_ENV', config('app.env'), 'production'),
            $this->mustEqual('APP_DEBUG', config('app.debug'), false),
            $this->mustBePresent('APP_KEY', config('app.key')),
            $this->mustHaveMinimumLength('AUDIT_HASH_KEY', config('runtime.audit_hash_key'), 32),
            $this->mustDiffer(
                'AUDIT_HASH_KEY',
                config('runtime.audit_hash_key'),
                'APP_KEY',
                config('app.key')
            ),
            $this->mustStartWith('APP_URL', config('app.url'), 'https://'),
            $this->mustEqual('DB_CONNECTION', config('database.default'), 'pgsql'),
            $this->mustNotBeOneOf('QUEUE_CONNECTION', config('queue.default'), ['sync', 'null']),
            $this->mustNotBeOneOf('SESSION_DRIVER', config('session.driver'), ['array', 'file', 'cookie']),
            $this->mustNotBeOneOf('CACHE_STORE', config('cache.default'), ['array', 'file', 'null']),
            $this->mustBeConfiguredFilesystemDisk(
                'AVATAR_DISK',
                config('filesystems.avatar_disk'),
                config('filesystems.disks', [])
            ),
            $this->mustNotBeOneOf('MAIL_MAILER', config('mail.default'), ['array', 'log']),
            $this->mustBePresent('MAIL_FROM_ADDRESS', config('mail.from.address')),
            $this->mustBePresent('BREVO_KEY', config('services.brevo.key')),
            $this->mustHaveMinimumLength('BREVO_WEBHOOK_TOKEN', config('services.brevo.webhook_token'), 32),
            $this->mustBePresent('STRIPE_KEY', config('services.stripe.key')),
            $this->mustBePresent('STRIPE_SECRET', config('services.stripe.secret')),
            $this->mustBePresent('STRIPE_WEBHOOK_SECRET', config('services.stripe.webhook_secret')),
            $this->mustBePresent('STRIPE_PRICE_STARTER', config('billing.catalog.starter.stripe_price')),
            $this->mustBePresent('STRIPE_PRICE_PULSE', config('billing.catalog.pulse.stripe_price')),
            $this->mustBePositiveInteger(
                'BILLING_PRICE_STARTER_CENTS',
                config('billing.catalog.starter.price_cents')
            ),
            $this->mustBePositiveInteger(
                'BILLING_PRICE_PULSE_CENTS',
                config('billing.catalog.pulse.price_cents')
            ),
            $this->mustEqual('SESSION_SECURE_COOKIE', config('session.secure'), true),
        ]));

        if (! (bool) $this->option('skip-connectivity')) {
            try {
                DB::connection()->select('select 1');
            } catch (Throwable $exception) {
                $failures[] = 'DATABASE_CONNECTIVITY: unable to connect to the configured database.';
            }
        }

        if ($failures !== []) {
            $this->error('Production configuration check failed.');
            foreach ($failures as $failure) {
                $this->line(' - '.$failure);
            }

            return self::FAILURE;
        }

        $this->info('Production configuration check passed.');

        return self::SUCCESS;
    }

    protected function mustEqual(string $name, mixed $actual, mixed $expected): ?string
    {
        return $actual === $expected
            ? null
            : "{$name}: expected ".var_export($expected, true).'.';
    }

    protected function mustBePresent(string $name, mixed $actual): ?string
    {
        return is_string($actual) && trim($actual) !== ''
            ? null
            : "{$name}: a non-empty value is required.";
    }

    protected function mustStartWith(string $name, mixed $actual, string $prefix): ?string
    {
        return is_string($actual) && str_starts_with($actual, $prefix)
            ? null
            : "{$name}: must start with {$prefix}.";
    }

    protected function mustHaveMinimumLength(string $name, mixed $actual, int $length): ?string
    {
        return is_string($actual) && strlen($actual) >= $length
            ? null
            : "{$name}: must contain at least {$length} characters.";
    }

    protected function mustDiffer(
        string $name,
        mixed $actual,
        string $otherName,
        mixed $other
    ): ?string {
        return is_string($actual) && $actual !== $other
            ? null
            : "{$name}: must be distinct from {$otherName}.";
    }

    protected function mustNotBeOneOf(string $name, mixed $actual, array $disallowed): ?string
    {
        return ! in_array($actual, $disallowed, true)
            ? null
            : "{$name}: ".var_export($actual, true).' is not permitted in production.';
    }

    protected function mustBePositiveInteger(string $name, mixed $actual): ?string
    {
        return filter_var($actual, FILTER_VALIDATE_INT) !== false && (int) $actual > 0
            ? null
            : "{$name}: a positive integer number of cents is required.";
    }

    protected function mustBeConfiguredFilesystemDisk(string $name, mixed $actual, array $disks): ?string
    {
        return is_string($actual) && trim($actual) !== '' && array_key_exists($actual, $disks)
            ? null
            : "{$name}: must name a configured Laravel filesystem disk.";
    }
}
