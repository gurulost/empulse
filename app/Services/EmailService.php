<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected string $senderName = 'Workfitdx';
    protected string $senderEmail = 'billing@workfitdx.com';
    protected string $adminEmail = 'empulse@wercinstitute.org';

    protected function providerUnavailableResponse(): array
    {
        return [
            'status' => 503,
            'message' => 'Email delivery is unavailable because Brevo is not configured for this environment.',
        ];
    }

    public function sendLetter(string $email, string $name, string $subject, string $content): array
    {
        if (App::environment('testing')) {
            return ['status' => 200];
        }

        if (empty(config('services.brevo.key'))) {
            Log::warning('Email send skipped: Brevo is not configured', [
                'recipient' => $email,
                'subject' => $subject,
            ]);

            return $this->providerUnavailableResponse();
        }

        try {
            $response = Http::withHeaders([
                'api-key' => config('services.brevo.key'),
                'Content-Type' => 'application/json'
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $this->senderName,
                    'email' => $this->senderEmail
                ],
                'to' => [
                    [
                        'email' => $email,
                        'name' => $name
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $content
            ]);

            if ($response->successful()) {
                return ['status' => 200];
            }

            Log::warning('Email send failed', ['status' => $response->status(), 'body' => $response->body()]);
            return ['status' => $response->status(), 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Email send exception', ['error' => $e->getMessage()]);
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    public function sendContactForm(string $name, string $email, string $phone): array
    {
        $content = view('mail', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone
        ])->render();

        return $this->sendToAdmin('From customer', $content);
    }

    public function sendPasswordReset(string $email, string $name, string $token): array
    {
        $content = view('auth.passwords.letter', [
            'name' => $name,
            'email' => $email,
            'token' => $token
        ])->render();

        return $this->sendLetter($email, $name, 'Reset password', $content);
    }

    public function sendSurveyInvitation(string $email, string $name, string $surveyUrl, string $companyName, ?string $waveLabel = null): array
    {
        $content = view('emails.survey-invitation', [
            'name' => $name,
            'surveyUrl' => $surveyUrl,
            'companyName' => $companyName,
            'waveLabel' => $waveLabel,
        ])->render();

        return $this->sendLetter($email, $name, "{$companyName} survey invitation", $content);
    }

    public function sendToAdmin(string $subject, string $content): array
    {
        if (App::environment('testing')) {
            return ['status' => 200];
        }

        if (empty(config('services.brevo.key'))) {
            Log::warning('Admin email send skipped: Brevo is not configured', [
                'subject' => $subject,
            ]);

            return $this->providerUnavailableResponse();
        }

        try {
            $response = Http::withHeaders([
                'api-key' => config('services.brevo.key'),
                'Content-Type' => 'application/json'
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => $this->senderName,
                    'email' => $this->senderEmail
                ],
                'to' => [
                    [
                        'email' => $this->adminEmail,
                        'name' => $this->senderName
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $content
            ]);

            if ($response->successful()) {
                return ['status' => 200];
            }

            return ['status' => $response->status(), 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Admin email send exception', ['error' => $e->getMessage()]);
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
