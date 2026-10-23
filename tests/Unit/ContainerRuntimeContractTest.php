<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ContainerRuntimeContractTest extends TestCase
{
    public function test_web_image_has_an_explicit_rootless_guarded_runtime(): void
    {
        $docker = $this->source('Dockerfile');
        $entrypoint = $this->source('docker/entrypoint.sh');

        $this->assertStringContainsString('php:8.5.12-fpm-alpine3.24@sha256:', $docker);
        $this->assertStringContainsString('USER www-data', $docker);
        $this->assertStringContainsString('STOPSIGNAL SIGTERM', $docker);
        $this->assertStringContainsString('EXPOSE 8080', $docker);
        $this->assertStringContainsString('CMD ["web"]', $docker);
        $this->assertLessThan(
            strpos($entrypoint, 'php-fpm -F &'),
            strpos($entrypoint, 'php artisan app:production-check')
        );
        $this->assertStringNotContainsString('APP_ENV', $entrypoint);
        $this->assertStringContainsString("\nwait -n\n", $entrypoint);
        $this->assertStringNotContainsString('wait -n "$php_pid"', $entrypoint);
        $this->assertStringContainsString('kill -QUIT', $entrypoint);
    }

    public function test_only_the_front_controller_executes_php_and_fpm_is_loopback_only(): void
    {
        $nginx = $this->source('docker/nginx/nginx.conf');
        $this->assertStringContainsString('root /var/www/html/public;', $nginx);
        $this->assertStringContainsString('location = /index.php', $nginx);
        $this->assertStringContainsString('location ~* \\.php(?:/|$)', $nginx);
        $this->assertStringContainsString('fastcgi_param HTTP_PROXY "";', $nginx);
        $this->assertStringContainsString('deny all;', $nginx);
        $this->assertStringContainsString('listen = 127.0.0.1:9000', $this->source('docker/php/fpm.conf'));
    }

    public function test_request_logs_do_not_include_bearer_paths_or_query_strings(): void
    {
        $nginx = $this->source('docker/nginx/nginx.conf');
        preg_match('/log_format empulse ([^;]+);/', $nginx, $match);
        $this->assertNotEmpty($match);
        foreach (['$uri', '$request_uri', '$request ', '$args', '$http_', '$remote_addr'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $match[1]);
        }
        $this->assertStringContainsString('error_log /dev/null;', $nginx);
        $this->assertStringContainsString('access.format = "%m %s %d"', $this->source('docker/php/fpm.conf'));
    }

    public function test_postgresql_uses_its_nonroot_entrypoint_without_the_unused_go_helper(): void
    {
        $postgres = $this->source('docker/postgres/Dockerfile');
        $this->assertStringContainsString('postgres:16.15-alpine@sha256:', $postgres);
        $this->assertStringContainsString('RUN rm /usr/local/bin/gosu', $postgres);
        $this->assertStringContainsString('USER postgres', $postgres);
    }

    private function source(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);
        $this->assertNotFalse($contents);

        return $contents;
    }
}
