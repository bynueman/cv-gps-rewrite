<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards Pengguna & Pengaturan — editor accounts are blocked at the
 * route level (not just a hidden sidebar item), including direct URL
 * access.
 */
class EnsureUserIsSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isSuperadmin()) {
            abort(403, 'Halaman ini hanya untuk superadmin.');
        }

        return $next($request);
    }
}
