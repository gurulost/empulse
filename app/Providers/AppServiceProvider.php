<?php

namespace App\Providers;

use App\Models\AccountInvitation;
use App\Models\Companies;
use App\Models\Subscription;
use App\Models\Survey;
use App\Models\SurveyVersion;
use App\Models\SurveyWave;
use App\Models\User;
use App\Observers\PrivilegedChangeObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
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
        URL::forceRootUrl((string) config('app.url'));

        // Use Bootstrap 5 pagination throughout the app
        Paginator::useBootstrapFive();
        Cashier::useCustomerModel(Companies::class);
        Cashier::useSubscriptionModel(Subscription::class);

        foreach ([
            Companies::class,
            User::class,
            Survey::class,
            SurveyVersion::class,
            SurveyWave::class,
            AccountInvitation::class,
        ] as $model) {
            $model::observe(PrivilegedChangeObserver::class);
        }

        Model::preventLazyLoading(! app()->isProduction());
        Schema::defaultStringLength(191);
    }
}
