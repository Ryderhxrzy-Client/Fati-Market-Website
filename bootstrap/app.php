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
        // The site runs behind the host's proxy, so the scheme and host it
        // forwards are what route() must build URLs from - otherwise the
        // login form posts to http://, the proxy answers with a redirect,
        // and the browser re-sends it as a GET.
        $middleware->trustProxies(at: '*');

        // The phone turnover page proves itself with the one-item key from
        // the QR it was opened with, not with a session - and a phone that
        // sat in a pocket between the scan and the photographs would
        // otherwise post with a session cookie the browser had dropped.
        $middleware->validateCsrfTokens(except: [
            'turnover/*',
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
