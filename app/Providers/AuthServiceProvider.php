<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CompanyWorker;
use App\Policies\UserPolicy;
use App\Policies\CompanyWorkerPolicy;
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
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('manage-email', function (User $authUser, string $email) {
            if ((int)($authUser->is_admin ?? 0) === 1) {
                return true;
            }

            $companyId = (int)$authUser->company_id;
            $uUsers = User::where('email', $email)->first();
            $uWorkers = DB::table('company_worker')->where('email', $email)->first();

            if (!$uUsers && !$uWorkers) {
                return false;
            }

            if ($uUsers && (int)$uUsers->company_id === $companyId) {
                return true;
            }
            if ($uWorkers && (int)$uWorkers->company_id === $companyId) {
                return true;
            }

            return false;
        });
    }
}
