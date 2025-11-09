<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Schema;

class PaymentController extends Controller
{
    public function payment()
    {
        return view('payment');
    }

    public function payment_success()
    {
        try {
            $updateCompanyTariff = DB::table("users")->where("company_title", \Auth::user()->company_title)->update(["tariff" => 1]);
            return redirect()->route('home');
        } catch(\Exception $e) {
            return view('responses_error');
        }
    }

    public function payment_error() {
        return view('responses_error');
    }
}
