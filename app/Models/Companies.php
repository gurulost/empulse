<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;

class Companies extends Model
{
    use Billable, HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'manager',
        'manager_email',
        'status',
        'closed_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function entitlement()
    {
        return $this->hasOne(OrganizationEntitlement::class, 'company_id');
    }

    public function billingAdmins()
    {
        return $this->hasMany(OrganizationBillingAdmin::class, 'company_id');
    }

    /**
     * @return HasMany<DiagnosticFinding, $this>
     */
    public function diagnosticFindings(): HasMany
    {
        return $this->hasMany(DiagnosticFinding::class, 'company_id');
    }

    /**
     * Cashier's legacy schema names the billable key user_id. In Empulse the
     * value is the durable company id.
     */
    public function subscriptions()
    {
        return $this->hasMany(Cashier::$subscriptionModel, 'user_id')
            ->orderBy('created_at', 'desc');
    }

    public function getCompanyList()
    {
        return DB::table('companies')
            ->select([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'users.tariff',
            ])
            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
            ->groupBy([
                'companies.id',
                'companies.title',
                'companies.manager',
                'companies.manager_email',
                'users.tariff',
            ])
            ->orderBy('companies.id', 'desc')
            ->paginate(10);
    }

    public function getCompanyUsers($id)
    {
        return DB::table('users')
//            ->select([
//                'companies.id',
//                'companies.title',
//                'companies.manager',
//                'companies.manager_email',
//                'users.tariff',
//            ])
//            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
//            ->orderBy('companies.id', 'desc')
            ->where('users.company_id', $id)
            ->paginate(10);

        //        return DB::table('company_worker')
        // //            ->select([
        // //                'companies.id',
        // //                'companies.title',
        // //                'companies.manager',
        // //                'companies.manager_email',
        // //                'users.tariff',
        // //            ])
        // //            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
        // //            ->orderBy('companies.id', 'desc')
        //            ->where('company_worker.company_id', $id)
        //            ->paginate(10);
    }

    public function getSubscriptionList()
    {
        return DB::table('companies')
            ->select([
                DB::raw('COUNT(companies.id) as count_companies'),
                'users.tariff',
            ])
            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
            ->where('users.company', 1)
            ->groupBy('users.tariff')
            ->orderBy('users.tariff', 'desc')
            ->paginate(10);
    }
}
