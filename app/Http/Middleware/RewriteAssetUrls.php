<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\View;
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
        
        if (!$this->shouldRewrite($response)) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === null) {
            return $response;
        }

        $original = method_exists($response, 'getOriginalContent')
            ? $response->getOriginalContent()
            : null;

        $rewritten = preg_replace(
            '/(href|src)="https?:\/\/[^\/]+(\/(build\/assets\/[^"]+))"/i',
            '$1="$2"',
            $content
        );

        if (is_string($rewritten)) {
            $response->setContent($rewritten);

            if ($original instanceof View) {
                $response->original = $original;
            }
        }

        return $response;
    }

    protected function shouldRewrite(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');

        return $contentType && str_contains($contentType, 'text/html');
    }
}
