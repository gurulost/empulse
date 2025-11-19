<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add middleware to rewrite asset URLs to relative paths
        $middleware->append(\App\Http\Middleware\RewriteAssetUrls::class);
        
        $middleware->alias([
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
