<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailService
{
    public function sendLetter(string $email, string $name, string $subject, string $content): array
    {
        try {
            $response = Http::withHeaders([
                'api-key' => env("BREVO_API_KEY"),
                'Content-Type' => 'application/json'
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => 'Workfitdx',
                    'email' => 'billing@workfitdx.com'
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

            return ['status' => $response->status(), 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
