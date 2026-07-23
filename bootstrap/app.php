<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);

        // The app only registers `admin.login` — without this, Laravel's
        // default Authenticate middleware falls back to route('login'),
        // which doesn't exist, and every guest hitting a protected
        // /admin/* route gets a 500 instead of a redirect to sign in.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->alias([
            'superadmin' => \App\Http\Middleware\EnsureUserIsSuperadmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
