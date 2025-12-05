<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactUs extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $phone;

    public function __construct($name, $email, $phone)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
    }

    public function build()
    {
        return $this->view('mail', [
            "name" => $this->name,
            "email" => $this->email,
            "phone" => $this->phone,
        ]);
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Contact Us',
        );
    }

    public function content()
    {
        return new Content(
            view: 'mail',
        );
    }

    public function attachments()
    {
        return [];
    }
}
