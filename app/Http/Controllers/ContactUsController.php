<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ContactUs;
use Illuminate\Support\Facades\Mail;
use App\Services\EmailService;

class ContactUsController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index()
    {
        return view('contact-us');
    }

    public function sendForm(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        
        $this->emailService->sendContactForm($name, $email, $phone);

        sleep(4);
        
        if (\Auth::check()) {
            return redirect('home');
        }
        
        return redirect()->route('contact.response');
    }

    public function response()
    {
        return view('contact-us-response');
    }
}
