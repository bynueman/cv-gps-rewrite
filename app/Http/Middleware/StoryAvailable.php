<?php

namespace App\Http\Middleware;

use App\Support\StoryAvailability;
use App\Support\StoryDatabase;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class StoryAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        // Public validation must return JSON instead of flashing story text into website sessions.
        if (! $request->is('admin/*')) {
            $request->headers->set('Accept', 'application/json');
        }
        abort_unless(config('stories.enabled'), 404);
        if (! $request->is('admin/*')) {
            abort_unless(StoryAvailability::publicEnabled(), 503, 'Ruang Cerita belum dibuka atau panduan belum disahkan.');
        }
        try {
            StoryDatabase::ensureConfigured();
        } catch (RuntimeException) {
            abort(503, 'Penyimpanan Ruang Cerita belum dikonfigurasi.');
        }

        try {
            return $next($request);
        } catch (QueryException) {
            // SQL errors can contain story bindings; never pass them to the application logger.
            abort(503, 'Ruang Cerita sementara tidak tersedia.');
        }
    }
}
