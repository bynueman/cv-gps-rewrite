<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Support\UserAgentParser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cookie-free hit logging for public pages only — no session, no
 * external analytics service. Raw IP is never persisted: visitor_hash
 * is a one-way hash of ip+user-agent+date, just enough to compute daily
 * "uniques" during aggregation without being able to re-identify anyone.
 * Bots are filtered by user-agent so they don't inflate the charts.
 */
class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->record($request);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'sitemap.xml', 'robots.txt', 'up')) {
            return false;
        }

        return ! UserAgentParser::isBot($request->userAgent());
    }

    private function record(Request $request): void
    {
        try {
            $referrerDomain = null;
            if ($referer = $request->header('referer')) {
                $host = parse_url($referer, PHP_URL_HOST);
                if ($host && $host !== $request->getHost()) {
                    $referrerDomain = $host;
                }
            }

            PageView::create([
                'path' => '/'.ltrim($request->path(), '/'),
                'viewed_on' => now()->toDateString(),
                'browser' => UserAgentParser::browser($request->userAgent()),
                'device' => UserAgentParser::device($request->userAgent()),
                'referrer_domain' => $referrerDomain,
                'visitor_hash' => hash('sha256', $request->ip().$request->userAgent().now()->toDateString()),
            ]);
        } catch (\Throwable $e) {
            // Analytics must never break the page it's measuring.
            Log::warning('Failed to record page view', ['error' => $e->getMessage()]);
        }
    }
}
