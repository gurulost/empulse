<?php

namespace App\Providers;

use App\Models\Subscription;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Use Bootstrap 5 pagination throughout the app
        Paginator::useBootstrapFive();
        Cashier::useSubscriptionModel(Subscription::class);
        
        // Force asset URLs to use request URL for Replit proxy compatibility
        // This ensures assets URLs match the domain the user is accessing from
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
