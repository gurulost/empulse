<?php

namespace Tests\Feature;

use ErrorException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Env;
use Tests\CreatesApplication;

class DatabaseTlsConfigurationTest extends TestCase
{
    use CreatesApplication;

    public function test_mysql_ca_setting_remains_enforced_without_php_85_deprecations(): void
    {
        if (! extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('PDO MySQL is not installed on this test runtime.');
        }

        $repository = Env::getRepository();
        $previous = $repository->get('MYSQL_ATTR_SSL_CA');
        $repository->set('MYSQL_ATTR_SSL_CA', '/synthetic-test-only/ca.pem');
        set_error_handler(function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        });
        try {
            $database = require config_path('database.php');
            foreach (['mysql', 'mysql_test'] as $connection) {
                $this->assertContains('/synthetic-test-only/ca.pem', $database['connections'][$connection]['options']);
            }
        } finally {
            restore_error_handler();
            $previous === null ? $repository->clear('MYSQL_ATTR_SSL_CA') : $repository->set('MYSQL_ATTR_SSL_CA', $previous);
        }
    }
}
