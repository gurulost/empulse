<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Companies extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "title",
        "manager",
        "manager_email"
    ];

    public function getCompanyList(){
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

    public function deleteUser($id){
        $user = DB::table('company_worker')->where(['id' => $id])->first();
        if ($user) {
            DB::table('users')->where(['email' => $user->email])->delete();
            DB::table('company_worker')->where(['id' => $id])->delete();
        }
    }

    public function getCompanyUsers($id){
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
////            ->select([
////                'companies.id',
////                'companies.title',
////                'companies.manager',
////                'companies.manager_email',
////                'users.tariff',
////            ])
////            ->leftJoin('users', 'users.company_id', '=', 'companies.id')
////            ->orderBy('companies.id', 'desc')
//            ->where('company_worker.company_id', $id)
//            ->paginate(10);
    }

    public function getSubscriptionList(){
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
