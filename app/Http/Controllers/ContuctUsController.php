<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ContuctUs;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class ContuctUsController extends Controller
{
    public function index()
    {
        return view('ContuctUs');
    }

    public function send_letter($subject, $content) {
        $response = Http::withHeaders([
            'api-key' => config('services.brevo.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            "sender" => [
                "name" => "Workfitdx",
                "email" => "billing@workfitdx.com",
            ],
            "to" => [
                [
                    "email" => "empulse@wercinstitute.org",
                    "name" => "Workfitdx",
                ],
            ],
            "subject" => $subject,
            "htmlContent" => $content,
        ]);

        return $response->body();
    }

    public function sendForm(Request $r)
    {
        $name = $r->input('name');
        $email = $r->input('email');
        $phone = $r->input('phone');
        $this->send_letter("From customer", view("mail", [
                "name" => $name,
                "email" => $email,
                "phone" => $phone
            ])->render()
        );

        sleep(4);
        return redirect('home');
    }

    public function response()
    {
        return view('ContuctUs-response');
    }

}
