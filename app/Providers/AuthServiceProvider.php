<?php

namespace App\Providers;

use App\Models\Companies;
use App\Models\CompanyWorker;
use App\Models\User;
use App\Policies\CompanyWorkerPolicy;
use App\Policies\TeamManagementPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        CompanyWorker::class => CompanyWorkerPolicy::class,
        Companies::class => TeamManagementPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

    }
}
