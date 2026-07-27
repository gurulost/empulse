<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\Request;

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
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);
        if (! empty($data['website'])) {
            return redirect()->route('contact.response');
        }
        $result = $this->emailService->sendContactForm(
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['message']
        );
        if ((int) ($result['status'] ?? 500) >= 400) {
            return back()
                ->withInput($request->except(['website']))
                ->withErrors('Your message could not be delivered right now. Please try again shortly.');
        }

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
