<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \Illuminate\Database\Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            return new \App\Database\NeonPostgresConnection($connection, $database, $prefix, $config);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        
        if ($this->app->runningInConsole() === false && request()) {
            $url = request()->getSchemeAndHttpHost();
            config(['app.url' => $url]);
            \URL::forceRootUrl($url);
        } else {
            \URL::forceScheme('https');
        }
        
        Model::preventLazyLoading(!app()->isProduction());
        Schema::defaultStringLength(191);

    }
}
