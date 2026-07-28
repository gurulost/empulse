<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), geolocation=(), microphone=(), payment=(self)'
        );
        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "img-src 'self' data: https:",
                "font-src 'self' data: https://fonts.gstatic.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "script-src 'self' 'unsafe-inline' https://js.stripe.com https://www.google.com https://www.gstatic.com",
                "connect-src 'self' https://api.stripe.com",
                'frame-src https://js.stripe.com https://hooks.stripe.com https://www.google.com',
            ])
        );

        $releaseSha = config('runtime.release_sha');
        if (is_string($releaseSha) && preg_match('/\A[0-9a-f]{40}\z/', $releaseSha) === 1) {
            $response->headers->set('X-Empulse-Release', $releaseSha);
        }

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
