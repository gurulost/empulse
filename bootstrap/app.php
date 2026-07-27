<?php

use App\Http\Middleware\EnsureEmailBelongsToCompany;
use App\Http\Middleware\Payment;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RequireCapability;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\Welcome;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TRUSTED_PROXIES', ''))
        )));

        $middleware->append(SecurityHeaders::class);
        $middleware->trustHosts(
            at: static function (): array {
                $host = parse_url((string) config('app.url'), PHP_URL_HOST);

                return $host ? ['^'.preg_quote($host, '/').'$'] : [];
            },
            subdomains: false,
        );
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'brevo/webhook',
        ]);

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'capability' => RequireCapability::class,
            'welcome' => Welcome::class,
            'payment' => Payment::class,
            'tenant.email' => EnsureEmailBelongsToCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
