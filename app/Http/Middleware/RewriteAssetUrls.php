<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RewriteAssetUrls
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only process HTML responses
        if ($response->headers->get('Content-Type') && 
            str_contains($response->headers->get('Content-Type'), 'text/html')) {
            
            $content = $response->getContent();
            
            // Replace absolute asset URLs with relative paths
            // This ensures assets work correctly behind Replit's proxy
            $content = preg_replace(
                '/(href|src)="https?:\/\/[^\/]+(\/(build\/assets\/[^"]+))"/i',
                '$1="$2"',
                $content
            );
            
            $response->setContent($content);
        }
        
        return $response;
    }
}
