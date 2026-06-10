<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // The theme cookie is written client-side (plaintext) and read during
        // SSR to render the correct theme, so it must not be encrypted.
        $middleware->encryptCookies(except: ['theme']);

        // The analytics beacon is an unauthenticated, sessionless POST from the
        // public page, so it carries no CSRF token.
        $middleware->validateCsrfTokens(except: ['beacon']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('beacon'),
        );
    })->create();
