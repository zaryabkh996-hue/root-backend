<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Prepend rate limiter to API routes (60 requests/minute)
        $middleware->api(prepend: [
            'throttle:60,1',
        ]);

        // Append global security headers
        $middleware->append(\App\Http\Middleware\AddSecurityHeaders::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminRole::class,
            'tier' => \App\Http\Middleware\CheckSubscriptionTier::class,
            'returned_traveller' => \App\Http\Middleware\EnsureReturnedTraveller::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                if (!config('app.debug')) {
                    // Do not override standard HTTP, authentication, or validation exceptions
                    if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ||
                        $e instanceof \Illuminate\Validation\ValidationException ||
                        $e instanceof \Illuminate\Auth\AuthenticationException ||
                        $e instanceof \Illuminate\Auth\AccessDeniedException) {
                        return null; // Let Laravel handle it normally
                    }

                    // Hide raw database/code exceptions and return generic 500 error
                    return response()->json([
                        'success' => false,
                        'message' => 'An unexpected server error occurred. Please contact support.',
                    ], 500);
                }
            }
        });
    })->create();
