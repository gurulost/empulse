<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;

class PaymentController extends Controller
{
    public function payment()
    {
        return Redirect::route('plans.index');
    }

    public function payment_success()
    {
        return Redirect::route('billing.index')
            ->with('status', config('billing.success_message'));
    }

    public function payment_error()
    {
        return Redirect::route('plans.index')
            ->withErrors('Checkout was not completed. Choose a plan to try again.');
    }

    public function responses_error()
    {
        return view('errors.payment');
    }
}
