<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * X-Robots-Tag rather than a <meta> tag — works regardless of whether a
 * crawler parses the HTML/JS, and covers every response the admin area
 * returns (including the JSON upload endpoint), not just full page loads.
 */
class NoIndexAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
