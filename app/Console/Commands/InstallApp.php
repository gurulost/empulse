<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallApp extends Command
{
    protected $signature = 'app:install
        {--seed : Run the database seeders after migrating}
        {--seed-if-empty : Seed only when the users table is empty}
        {--force : Required when running in production}';

    protected $description = 'Run migrations (and optionally seed) to bootstrap the application database.';

    public function handle(): int
    {
        if (!$this->canRunInCurrentEnvironment()) {
            return self::FAILURE;
        }

        if (!$this->canConnectToDatabase()) {
            return self::FAILURE;
        }

        $this->info('Running migrations...');
        $migrateExitCode = $this->call('migrate', $this->migrateOptions());
        if ($migrateExitCode !== self::SUCCESS) {
            return $migrateExitCode;
        }

        if (!$this->shouldSeed()) {
            $this->info('Skipping seeders.');
            return self::SUCCESS;
        }

        $this->info('Seeding database...');
        $seedExitCode = $this->call('db:seed', $this->seedOptions());

        return $seedExitCode === self::SUCCESS ? self::SUCCESS : $seedExitCode;
    }

    protected function canRunInCurrentEnvironment(): bool
    {
        if (!app()->environment('production')) {
            return true;
        }

        if ((bool) $this->option('force')) {
            return true;
        }

        $this->error('Refusing to run in production without `--force`.');

        return false;
    }

    protected function canConnectToDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable $exception) {
            $this->error('Unable to connect to the database.');
            $this->line($exception->getMessage());

            return false;
        }
    }

    protected function migrateOptions(): array
    {
        $options = ['--no-interaction' => true];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        return $options;
    }

    protected function seedOptions(): array
    {
        $options = ['--no-interaction' => true];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        return $options;
    }

    protected function shouldSeed(): bool
    {
        if ((bool) $this->option('seed')) {
            return true;
        }

        if (!(bool) $this->option('seed-if-empty')) {
            return false;
        }

        try {
            if (!Schema::hasTable('users')) {
                return false;
            }

            return (int) DB::table('users')->count() === 0;
        } catch (Throwable) {
            return false;
        }
    }
}

