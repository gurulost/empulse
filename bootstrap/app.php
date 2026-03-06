<?php

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
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // Add middleware to rewrite asset URLs to relative paths
        $middleware->append(\App\Http\Middleware\RewriteAssetUrls::class);
        
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'manager' => \App\Http\Middleware\Manager::class,
            'teamlead' => \App\Http\Middleware\Teamlead::class,
            'chief' => \App\Http\Middleware\Chief::class,
            'admin' => \App\Http\Middleware\Admin::class,
            'workfit_admin' => \App\Http\Middleware\WorkfitAdmin::class,
            'welcome' => \App\Http\Middleware\Welcome::class,
            'payment' => \App\Http\Middleware\Payment::class,
            'tenant.email' => \App\Http\Middleware\EnsureEmailBelongsToCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
