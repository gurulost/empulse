<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SocialAuthService
{
    public function handleSocialLogin(
        string $provider,
        string $socialId,
        string $email,
        string $name,
        string $socialIdColumn
    ): array {
        if (empty($email)) {
            return [
                'success' => false,
                'error' => "Unable to retrieve email from {$provider} account.",
            ];
        }

        $result = DB::transaction(function () use ($socialIdColumn, $socialId, $email, $provider) {
            $existingUser = User::where($socialIdColumn, $socialId)->lockForUpdate()->first();
            if ($existingUser) {
                if ($existingUser->status === 'inactive') {
                    return ['success' => false, 'error' => 'This account is inactive.'];
                }
                if ($existingUser->company_id === null && ! $existingUser->is_admin) {
                    return [
                        'success' => false,
                        'error' => 'This identity is not linked to an Empulse company workspace.',
                    ];
                }

                return ['success' => true, 'user' => $existingUser];
            }

            if (User::where('email', $email)->exists()) {
                return [
                    'success' => false,
                    'error' => "Sign in with your password before linking {$provider}.",
                ];
            }

            return [
                'success' => false,
                'error' => 'Create a company workspace first or accept your organization invitation before using social sign-in.',
            ];
        });

        if ($result['success']) {
            Auth::login($result['user']);
        }

        return $result;
    }

    public function handleGoogleLogin(string $googleId, ?string $email, string $name): array
    {
        return $this->handleSocialLogin('Google', $googleId, $email ?? '', $name, 'google_id');
    }

    public function handleFacebookLogin(string $fbId, ?string $email, string $name): array
    {
        return $this->handleSocialLogin('Facebook', $fbId, $email ?? '', $name, 'fb_id');
    }
}
