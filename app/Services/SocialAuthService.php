<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
                'error' => "Unable to retrieve email from {$provider} account."
            ];
        }

        $existingUser = User::where($socialIdColumn, $socialId)->first();
        if ($existingUser) {
            Auth::login($existingUser);
            return ['success' => true, 'user' => $existingUser];
        }

        $userByEmail = User::where('email', $email)->first();
        if ($userByEmail) {
            $userByEmail->update([$socialIdColumn => $socialId]);
            Auth::login($userByEmail);
            return ['success' => true, 'user' => $userByEmail];
        }

        $newUser = User::create([
            'name' => $name,
            'email' => $email,
            $socialIdColumn => $socialId,
            'password' => Hash::make(Str::random(32)),
            'role' => 4,
            'company_id' => null,
        ]);

        Auth::login($newUser);
        return ['success' => true, 'user' => $newUser, 'is_new' => true];
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
